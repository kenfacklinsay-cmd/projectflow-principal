      </div>
    </main>
  </div>
  <footer class="footer">© 2026 PROJECTFLOW — Gestion de Projets Simplifiée</footer>
  <script>
    (function () {
      const toggleBtn = document.querySelector('.mobile-menu-toggle');
      if (!toggleBtn) return;

      const closeSidebar = () => document.body.classList.remove('sidebar-open');
      const toggleSidebar = () => document.body.classList.toggle('sidebar-open');

      toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSidebar();
      });

      document.addEventListener('click', (e) => {
        if (!document.body.classList.contains('sidebar-open')) return;
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
          closeSidebar();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
      });
    })();
  </script>
</body>
</html>
