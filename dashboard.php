<?php
$pageTitle = 'Tableau de bord - PROJECTFLOW';
$currentPage = 'dashboard';
$pageContentExtraClass = 'page-content--dashboard';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

if (Auth::estAdmin()) {
    $stmt = $db->query('SELECT COUNT(*) AS n FROM projets');
} elseif (Auth::estChefProjet()) {
    $stmt = $db->prepare('SELECT COUNT(*) AS n FROM projets WHERE chef_projet_id = ?');
    $stmt->execute([$user['id']]);
} else {
    $stmt = $db->prepare('SELECT COUNT(DISTINCT p.id) AS n FROM projets p JOIN taches t ON t.projet_id = p.id WHERE t.assignee_id = ?');
    $stmt->execute([$user['id']]);
}
$nbProjets = (int) $stmt->fetch()['n'];

if (Auth::estAdmin()) {
    $stmt = $db->query('SELECT COUNT(*) AS n FROM projets WHERE statut = "termine"');
    $stmt2 = $db->query('SELECT COUNT(*) AS n FROM projets WHERE statut = "en_cours"');
} elseif (Auth::estChefProjet()) {
    $stmt = $db->prepare('SELECT COUNT(*) AS n FROM projets WHERE chef_projet_id = ? AND statut = "termine"');
    $stmt->execute([$user['id']]);
    $stmt2 = $db->prepare('SELECT COUNT(*) AS n FROM projets WHERE chef_projet_id = ? AND statut = "en_cours"');
    $stmt2->execute([$user['id']]);
} else {
    $stmt = $db->prepare('SELECT COUNT(DISTINCT p.id) AS n FROM projets p JOIN taches t ON t.projet_id = p.id WHERE t.assignee_id = ? AND p.statut = "termine"');
    $stmt->execute([$user['id']]);
    $stmt2 = $db->prepare('SELECT COUNT(DISTINCT p.id) AS n FROM projets p JOIN taches t ON t.projet_id = p.id WHERE t.assignee_id = ? AND p.statut = "en_cours"');
    $stmt2->execute([$user['id']]);
}
$projetsTermines = (int) $stmt->fetch()['n'];
$projetsEnCours = (int) $stmt2->fetch()['n'];
$projetsTotal = $nbProjets;

if (Auth::estAdmin()) {
    $stmt = $db->query('SELECT COUNT(*) AS n FROM taches WHERE statut != "annule"');
    $stmt2 = $db->query('SELECT COUNT(*) AS n FROM taches WHERE statut = "termine"');
} elseif (Auth::estChefProjet()) {
    $stmt = $db->prepare('SELECT COUNT(*) AS n FROM taches t JOIN projets p ON t.projet_id = p.id WHERE p.chef_projet_id = ? AND t.statut != "annule"');
    $stmt->execute([$user['id']]);
    $stmt2 = $db->prepare('SELECT COUNT(*) AS n FROM taches t JOIN projets p ON t.projet_id = p.id WHERE p.chef_projet_id = ? AND t.statut = "termine"');
    $stmt2->execute([$user['id']]);
} else {
    $stmt = $db->prepare('SELECT COUNT(*) AS n FROM taches WHERE assignee_id = ? AND statut != "annule"');
    $stmt->execute([$user['id']]);
    $stmt2 = $db->prepare('SELECT COUNT(*) AS n FROM taches WHERE assignee_id = ? AND statut = "termine"');
    $stmt2->execute([$user['id']]);
}
$nbTaches = (int) $stmt->fetch()['n'];
$nbTachesTerminees = (int) $stmt2->fetch()['n'];

$pctProjetsTermines = $projetsTotal > 0 ? (int) round($projetsTermines / $projetsTotal * 100) : 0;
$pctProjetsEnCours = $projetsTotal > 0 ? (int) round($projetsEnCours / $projetsTotal * 100) : 0;
$pctTachesFaites = $nbTaches > 0 ? (int) round($nbTachesTerminees / $nbTaches * 100) : 0;
$tachesRestantes = max(0, $nbTaches - $nbTachesTerminees);
$pctRestant = $nbTaches > 0 ? (int) round($tachesRestantes / $nbTaches * 100) : 0;

$listLimit = 8;

if (Auth::estAdmin()) {
    $stmt = $db->query(
        'SELECT t.id, t.titre, t.statut, t.projet_id, t.assignee_id,
        u.prenom AS assignee_prenom, u.nom AS assignee_nom, u.photo AS assignee_photo,
        p.nom AS projet_nom
        FROM taches t
        JOIN projets p ON t.projet_id = p.id
        LEFT JOIN utilisateurs u ON t.assignee_id = u.id
        WHERE t.statut != "annule"
        ORDER BY t.date_creation DESC
        LIMIT ' . (int) $listLimit
    );
    $dashTaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (Auth::estChefProjet()) {
    $stmt = $db->prepare(
        'SELECT t.id, t.titre, t.statut, t.projet_id, t.assignee_id,
        u.prenom AS assignee_prenom, u.nom AS assignee_nom, u.photo AS assignee_photo,
        p.nom AS projet_nom
        FROM taches t
        JOIN projets p ON t.projet_id = p.id
        LEFT JOIN utilisateurs u ON t.assignee_id = u.id
        WHERE t.statut != "annule" AND p.chef_projet_id = ?
        ORDER BY t.date_creation DESC
        LIMIT ' . (int) $listLimit
    );
    $stmt->execute([$user['id']]);
    $dashTaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare(
        'SELECT t.id, t.titre, t.statut, t.projet_id, t.assignee_id,
        u.prenom AS assignee_prenom, u.nom AS assignee_nom, u.photo AS assignee_photo,
        p.nom AS projet_nom
        FROM taches t
        JOIN projets p ON t.projet_id = p.id
        LEFT JOIN utilisateurs u ON t.assignee_id = u.id
        WHERE t.statut != "annule" AND t.assignee_id = ?
        ORDER BY t.date_creation DESC
        LIMIT ' . (int) $listLimit
    );
    $stmt->execute([$user['id']]);
    $dashTaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (Auth::estAdmin()) {
    $stmt = $db->query(
        'SELECT p.id, p.nom, p.statut, p.chef_projet_id, v.progression_pct
        FROM projets p
        LEFT JOIN v_progression_projets v ON v.id = p.id
        ORDER BY p.date_creation DESC
        LIMIT ' . (int) $listLimit
    );
    $dashProjets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (Auth::estChefProjet()) {
    $stmt = $db->prepare(
        'SELECT p.id, p.nom, p.statut, p.chef_projet_id, v.progression_pct
        FROM projets p
        LEFT JOIN v_progression_projets v ON v.id = p.id
        WHERE p.chef_projet_id = ?
        ORDER BY p.date_creation DESC
        LIMIT ' . (int) $listLimit
    );
    $stmt->execute([$user['id']]);
    $dashProjets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare(
        'SELECT p.id, p.nom, p.statut, p.chef_projet_id, v.progression_pct
        FROM projets p
        LEFT JOIN v_progression_projets v ON v.id = p.id
        INNER JOIN taches t ON t.projet_id = p.id AND t.assignee_id = ?
        GROUP BY p.id
        ORDER BY p.date_creation DESC
        LIMIT ' . (int) $listLimit
    );
    $stmt->execute([$user['id']]);
    $dashProjets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fmt_n($n) {
    return number_format((int) $n, 0, ',', ' ');
}

function dash_initiales($prenom, $nom) {
    $p = mb_substr(trim((string) $prenom), 0, 1);
    $n = mb_substr(trim((string) $nom), 0, 1);
    if ($p === '' && $n === '') {
        return '?';
    }
    return mb_strtoupper($p . $n, 'UTF-8');
}

function dash_tone_from_user_id($id) {
    if (!$id) {
        return 'dash-tone-muted';
    }
    $tones = ['dash-tone-sky', 'dash-tone-amber', 'dash-tone-emerald', 'dash-tone-violet', 'dash-tone-rose'];
    return $tones[(int) $id % 5];
}

function dash_tache_peut_modifier(array $t) {
    if (Auth::estAdmin()) {
        return true;
    }
    if (Auth::estChefProjet() && Auth::peutVoirProjet((int) $t['projet_id'])) {
        return true;
    }
    if (Auth::estMembre() && (int) ($t['assignee_id'] ?? 0) === (int) Auth::getUser()['id']) {
        return true;
    }
    return false;
}

function dash_projet_peut_modifier(array $p) {
    return Auth::estAdmin() || (Auth::estChefProjet() && (int) $p['chef_projet_id'] === (int) Auth::getUser()['id']);
}

function dash_projet_dot_class($statut) {
    switch ($statut) {
        case 'termine':
            return 'dash-proj-dot--done';
        case 'en_attente':
            return 'dash-proj-dot--hold';
        case 'annule':
            return 'dash-proj-dot--cancel';
        case 'en_cours':
        default:
            return 'dash-proj-dot--active';
    }
}
?>

<div class="dash-hero">
  <h1 class="dash-hero-title">Tableau de bord</h1>
  <div class="dash-hero-user">
    <?php $photoUrl = photoProfilUrl($user); ?>
    <?php if ($photoUrl): ?>
      <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" class="dash-hero-avatar">
    <?php else: ?>
      <div class="dash-hero-avatar dash-hero-avatar-default"><i class="fas fa-user"></i></div>
    <?php endif; ?>
    <span class="dash-hero-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
    <span class="dash-hero-status" title="En ligne"></span>
  </div>
</div>

<div class="dash-stat-grid">
  <a href="<?= SITE_URL ?>/projets/index.php" class="dash-stat-card dash-stat-card--teal">
    <span class="dash-stat-icon" aria-hidden="true"><i class="fas fa-folder-open"></i></span>
    <span class="dash-stat-label">Projets</span>
    <span class="dash-stat-value"><?= fmt_n($projetsTotal) ?></span>
    <div class="dash-stat-foot">
      <span class="dash-stat-badge dash-stat-badge--up">+ <?= $pctProjetsTermines ?> % <i class="fas fa-arrow-trend-up" aria-hidden="true"></i></span>
      <span class="dash-stat-meta">terminés</span>
    </div>
  </a>

  <a href="<?= SITE_URL ?>/projets/index.php" class="dash-stat-card dash-stat-card--sky">
    <span class="dash-stat-icon" aria-hidden="true"><i class="fas fa-spinner"></i></span>
    <span class="dash-stat-label">En cours</span>
    <span class="dash-stat-value"><?= fmt_n($projetsEnCours) ?></span>
    <div class="dash-stat-foot">
      <span class="dash-stat-badge dash-stat-badge--up">+ <?= $pctProjetsEnCours ?> % <i class="fas fa-arrow-trend-up" aria-hidden="true"></i></span>
      <span class="dash-stat-meta">du total projets</span>
    </div>
  </a>

  <a href="<?= SITE_URL ?>/taches/index.php" class="dash-stat-card dash-stat-card--rose">
    <span class="dash-stat-icon" aria-hidden="true"><i class="fas fa-list-check"></i></span>
    <span class="dash-stat-label">Tâches</span>
    <span class="dash-stat-value"><?= fmt_n($nbTaches) ?></span>
    <div class="dash-stat-foot">
      <?php if ($pctRestant > 0): ?>
        <span class="dash-stat-badge dash-stat-badge--down">− <?= $pctRestant ?> % <i class="fas fa-arrow-trend-down" aria-hidden="true"></i></span>
        <span class="dash-stat-meta">reste à faire</span>
      <?php else: ?>
        <span class="dash-stat-badge dash-stat-badge--up">100 % <i class="fas fa-check" aria-hidden="true"></i></span>
        <span class="dash-stat-meta">complété</span>
      <?php endif; ?>
    </div>
  </a>

  <a href="<?= SITE_URL ?>/taches/index.php" class="dash-stat-card dash-stat-card--lavender">
    <span class="dash-stat-icon" aria-hidden="true"><i class="fas fa-circle-check"></i></span>
    <span class="dash-stat-label">Tâches terminées</span>
    <span class="dash-stat-value"><?= fmt_n($nbTachesTerminees) ?></span>
    <div class="dash-stat-foot">
      <span class="dash-stat-badge dash-stat-badge--up">+ <?= $pctTachesFaites ?> % <i class="fas fa-arrow-trend-up" aria-hidden="true"></i></span>
      <span class="dash-stat-meta">du volume total</span>
    </div>
  </a>
</div>

<div class="dash-panel-grid">
  <section class="dash-panel" aria-labelledby="dash-taches-heading">
    <h2 id="dash-taches-heading" class="dash-panel-title">Tâches récentes</h2>
    <div class="dash-panel-columns" aria-hidden="true">
      <span class="dash-panel-col-label">Assigné</span>
      <span class="dash-panel-col-label">Tâche</span>
    </div>
    <ul class="dash-panel-list">
      <?php foreach ($dashTaches as $t):
        $assigneeUser = [
            'photo' => $t['assignee_photo'] ?? null,
        ];
        $photoA = photoProfilUrl($assigneeUser);
        $tone = dash_tone_from_user_id((int) ($t['assignee_id'] ?? 0));
        $peutMod = dash_tache_peut_modifier($t);
        ?>
        <li class="dash-panel-item">
          <div class="dash-panel-row">
            <div class="dash-panel-avatar-wrap">
              <?php if ($photoA): ?>
                <img src="<?= htmlspecialchars($photoA) ?>" alt="" class="dash-panel-avatar-img">
              <?php else: ?>
                <span class="dash-panel-avatar-fallback <?= htmlspecialchars($tone) ?>"><?= htmlspecialchars(dash_initiales($t['assignee_prenom'] ?? '', $t['assignee_nom'] ?? '')) ?></span>
              <?php endif; ?>
            </div>
            <div class="dash-panel-main">
              <a href="<?= SITE_URL ?>/taches/voir.php?id=<?= (int) $t['id'] ?>" class="dash-panel-line"><?= htmlspecialchars($t['titre']) ?></a>
              <span class="dash-panel-sub"><?= htmlspecialchars($t['projet_nom']) ?> · <?= htmlspecialchars(statutTacheLabel($t['statut'])) ?></span>
            </div>
            <?php if ($peutMod): ?>
              <a href="<?= SITE_URL ?>/taches/editer.php?id=<?= (int) $t['id'] ?>" class="dash-panel-action" title="Modifier"><i class="fas fa-pencil" aria-hidden="true"></i></a>
            <?php else: ?>
              <a href="<?= SITE_URL ?>/taches/voir.php?id=<?= (int) $t['id'] ?>" class="dash-panel-action" title="Voir"><i class="fas fa-eye" aria-hidden="true"></i></a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if (empty($dashTaches)): ?>
      <p class="dash-panel-empty">Aucune tâche à afficher.</p>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/taches/index.php" class="dash-panel-footer-link">Voir toutes les tâches</a>
  </section>

  <section class="dash-panel" aria-labelledby="dash-projets-heading">
    <h2 id="dash-projets-heading" class="dash-panel-title">Projets récents</h2>
    <div class="dash-panel-columns" aria-hidden="true">
      <span class="dash-panel-col-label">Statut</span>
      <span class="dash-panel-col-label">Projet</span>
    </div>
    <ul class="dash-panel-list">
      <?php foreach ($dashProjets as $p):
        $pct = (int) ($p['progression_pct'] ?? 0);
        $dotClass = dash_projet_dot_class($p['statut']);
        $peutModP = dash_projet_peut_modifier($p);
        ?>
        <li class="dash-panel-item">
          <div class="dash-panel-row">
            <div class="dash-panel-avatar-wrap dash-panel-avatar-wrap--dot">
              <span class="dash-proj-dot <?= $dotClass ?>" title="<?= htmlspecialchars(statutProjetLabel($p['statut'])) ?>"></span>
            </div>
            <div class="dash-panel-main">
              <a href="<?= SITE_URL ?>/projets/voir.php?id=<?= (int) $p['id'] ?>" class="dash-panel-line"><?= htmlspecialchars($p['nom']) ?></a>
              <span class="dash-panel-sub"><?= htmlspecialchars(statutProjetLabel($p['statut'])) ?> · <?= $pct ?> %</span>
            </div>
            <?php if ($peutModP): ?>
              <a href="<?= SITE_URL ?>/projets/editer.php?id=<?= (int) $p['id'] ?>" class="dash-panel-action" title="Modifier"><i class="fas fa-pencil" aria-hidden="true"></i></a>
            <?php else: ?>
              <a href="<?= SITE_URL ?>/projets/voir.php?id=<?= (int) $p['id'] ?>" class="dash-panel-action" title="Voir"><i class="fas fa-eye" aria-hidden="true"></i></a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if (empty($dashProjets)): ?>
      <p class="dash-panel-empty">Aucun projet à afficher.</p>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/projets/index.php" class="dash-panel-footer-link">Voir tous les projets</a>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
