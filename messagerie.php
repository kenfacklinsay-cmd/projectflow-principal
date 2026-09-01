<?php
$pageTitle = 'Messagerie - PROJECTFLOW';
$currentPage = 'messagerie';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$db = Database::getInstance()->getConnection();
$myId = (int) $user['id'];

// Envoyer un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu'])) {
    $dest = (int)($_POST['destinataire_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');
    if ($dest && $contenu && $dest !== $myId) {
        $stmt = $db->prepare('INSERT INTO messages (expediteur_id, destinataire_id, contenu) VALUES (?, ?, ?)');
        $stmt->execute([$myId, $dest, $contenu]);
        creerNotification(
            $db,
            $dest,
            'Nouveau message',
            $user['prenom'] . ' ' . $user['nom'] . ' vous a envoyé un message : ' . mb_substr($contenu, 0, 80) . (mb_strlen($contenu) > 80 ? '…' : ''),
            SITE_URL . '/messagerie.php?avec=' . $myId,
            'message'
        );
        header('Location: ' . SITE_URL . '/messagerie.php?avec=' . $dest);
        exit;
    }
}

$avecId = isset($_GET['avec']) ? (int)$_GET['avec'] : null;

// Marquer comme lu quand on ouvre la conversation
if ($avecId) {
    $db->prepare('UPDATE messages SET lu = 1 WHERE destinataire_id = ? AND expediteur_id = ?')->execute([$myId, $avecId]);
}

// Liste des conversations : utilisateurs avec qui j'ai échangé + dernier message + non lus
$stmt = $db->prepare("
  SELECT u.id, u.prenom, u.nom,
    (SELECT m.contenu FROM messages m
     WHERE (m.expediteur_id = u.id AND m.destinataire_id = ?) OR (m.destinataire_id = u.id AND m.expediteur_id = ?)
     ORDER BY m.date_envoi DESC LIMIT 1) AS dernier_msg,
    (SELECT m.date_envoi FROM messages m
     WHERE (m.expediteur_id = u.id AND m.destinataire_id = ?) OR (m.destinataire_id = u.id AND m.expediteur_id = ?)
     ORDER BY m.date_envoi DESC LIMIT 1) AS dernier_date,
    (SELECT COUNT(*) FROM messages m WHERE m.expediteur_id = u.id AND m.destinataire_id = ? AND m.lu = 0) AS non_lus
  FROM utilisateurs u
  WHERE u.actif = 1 AND u.id != ?
  AND EXISTS (
    SELECT 1 FROM messages m
    WHERE (m.expediteur_id = u.id AND m.destinataire_id = ?) OR (m.destinataire_id = u.id AND m.expediteur_id = ?)
  )
  ORDER BY dernier_date DESC
");
$stmt->execute([$myId, $myId, $myId, $myId, $myId, $myId, $myId, $myId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tous les utilisateurs pour afficher les contacts (avec ou sans conversation)
$stmt = $db->prepare('SELECT id, prenom, nom FROM utilisateurs WHERE actif = 1 AND id != ? ORDER BY nom, prenom');
$stmt->execute([$myId]);
$tousContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajouter les contacts avec qui on n'a jamais échangé (pour pouvoir démarrer une conversation)
$idsEnConversation = array_column($conversations, 'id');
foreach ($tousContacts as $c) {
    if (!in_array((int)$c['id'], $idsEnConversation)) {
        $conversations[] = [
            'id' => $c['id'],
            'prenom' => $c['prenom'],
            'nom' => $c['nom'],
            'dernier_msg' => null,
            'dernier_date' => null,
            'non_lus' => 0
        ];
    }
}
// Re-trier par date (les sans date en dernier)
usort($conversations, function ($a, $b) {
    $da = $a['dernier_date'] ? strtotime($a['dernier_date']) : 0;
    $db = $b['dernier_date'] ? strtotime($b['dernier_date']) : 0;
    return $db <=> $da;
});

// Messages de la conversation sélectionnée
$messages = [];
$contactActuel = null;
if ($avecId) {
    foreach ($tousContacts as $c) {
        if ((int)$c['id'] === $avecId) {
            $contactActuel = $c;
            break;
        }
    }
    if (!$contactActuel) {
        $stmt = $db->prepare('SELECT id, prenom, nom FROM utilisateurs WHERE id = ?');
        $stmt->execute([$avecId]);
        $contactActuel = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($contactActuel) {
        $stmt = $db->prepare('
          SELECT id, expediteur_id, destinataire_id, contenu, date_envoi, lu
          FROM messages
          WHERE (expediteur_id = ? AND destinataire_id = ?) OR (expediteur_id = ? AND destinataire_id = ?)
          ORDER BY date_envoi ASC
        ');
        $stmt->execute([$myId, $avecId, $avecId, $myId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="chat-container">
  <aside class="chat-list">
    <div class="chat-list-header">
      <h2 class="chat-list-title">Messages</h2>
    </div>
    <div class="chat-list-search">
      <input type="text" class="chat-search-input" placeholder="Rechercher un contact..." id="search-contact">
    </div>
    <div class="chat-list-items" id="chat-list-items">
      <?php foreach ($conversations as $conv): ?>
        <a href="<?= SITE_URL ?>/messagerie.php?avec=<?= (int)$conv['id'] ?>" class="chat-list-item <?= $avecId === (int)$conv['id'] ? 'active' : '' ?>" data-name="<?= htmlspecialchars(mb_strtolower($conv['prenom'] . ' ' . $conv['nom'])) ?>">
          <div class="chat-list-avatar"><?= strtoupper(mb_substr($conv['prenom'], 0, 1) . mb_substr($conv['nom'], 0, 1)) ?></div>
          <div class="chat-list-body">
            <span class="chat-list-name"><?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?></span>
            <span class="chat-list-preview"><?= $conv['dernier_msg'] ? htmlspecialchars(mb_substr($conv['dernier_msg'], 0, 40)) . (mb_strlen($conv['dernier_msg']) > 40 ? '…' : '') : 'Aucun message' ?></span>
          </div>
          <?php if (!empty($conv['dernier_date'])): ?>
            <span class="chat-list-time"><?= date('H:i', strtotime($conv['dernier_date'])) ?></span>
          <?php endif; ?>
          <?php if (!empty($conv['non_lus']) && (int)$conv['non_lus'] > 0): ?>
            <span class="chat-list-badge"><?= (int)$conv['non_lus'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (empty($conversations)): ?>
      <p class="chat-list-empty">Aucun contact. Les conversations apparaîtront ici.</p>
    <?php endif; ?>
  </aside>

  <section class="chat-main">
    <?php if ($contactActuel): ?>
      <div class="chat-header">
        <div class="chat-header-avatar"><?= strtoupper(mb_substr($contactActuel['prenom'], 0, 1) . mb_substr($contactActuel['nom'], 0, 1)) ?></div>
        <div class="chat-header-info">
          <span class="chat-header-name"><?= htmlspecialchars($contactActuel['prenom'] . ' ' . $contactActuel['nom']) ?></span>
        </div>
      </div>
      <div class="chat-messages" id="chat-messages">
        <?php foreach ($messages as $m): ?>
          <?php $isMe = (int)$m['expediteur_id'] === $myId; ?>
          <div class="chat-bubble-wrap <?= $isMe ? 'sent' : 'received' ?>">
            <div class="chat-bubble">
              <?= nl2br(htmlspecialchars($m['contenu'])) ?>
            </div>
            <span class="chat-bubble-time"><?= date('H:i', strtotime($m['date_envoi'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" action="<?= SITE_URL ?>/messagerie.php?avec=<?= (int)$avecId ?>" class="chat-input-wrap">
        <input type="hidden" name="destinataire_id" value="<?= (int)$avecId ?>">
        <textarea name="contenu" class="chat-input" placeholder="Écrivez un message..." rows="1" required></textarea>
        <button type="submit" class="chat-send-btn" title="Envoyer"><i class="fas fa-paper-plane"></i></button>
      </form>
    <?php else: ?>
      <div class="chat-welcome">
        <div class="chat-welcome-icon"><i class="fas fa-comments"></i></div>
        <p class="chat-welcome-title">Messagerie PROJECTFLOW</p>
        <p class="chat-welcome-text">Choisissez une conversation dans la liste ou démarrez un échange avec un contact.</p>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
document.getElementById('search-contact')?.addEventListener('input', function() {
  var q = this.value.trim().toLowerCase();
  document.querySelectorAll('.chat-list-item').forEach(function(el) {
    el.style.display = (!q || el.getAttribute('data-name').indexOf(q) !== -1) ? '' : 'none';
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
