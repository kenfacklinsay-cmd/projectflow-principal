
/* =========================================================
   PROJECTFLOW - GESTION DU THEME
   Clair / Sombre
   ========================================================= */

(function () {

    "use strict";

    const THEME_KEY = "projectflow_theme";


    /* -------------------------------------------------------
       Récupérer le thème sauvegardé
       ------------------------------------------------------- */

    function getSavedTheme() {

        const savedTheme = localStorage.getItem(THEME_KEY);

        if (savedTheme === "dark" || savedTheme === "light") {
            return savedTheme;
        }

        return "light";
    }


    /* -------------------------------------------------------
       Appliquer le thème
       ------------------------------------------------------- */

    function applyTheme(theme) {

        if (theme !== "dark" && theme !== "light") {
            theme = "light";
        }

        document.documentElement.setAttribute(
            "data-theme",
            theme
        );

        updateThemeButtons(theme);
    }


    /* -------------------------------------------------------
       Sauvegarder le thème
       ------------------------------------------------------- */

    function saveTheme(theme) {

        localStorage.setItem(
            THEME_KEY,
            theme
        );

        applyTheme(theme);
    }


    /* -------------------------------------------------------
       Mettre à jour les boutons
       ------------------------------------------------------- */

    function updateThemeButtons(theme) {

        const buttons = document.querySelectorAll(
            "[data-theme-choice]"
        );

        buttons.forEach(function (button) {

            const buttonTheme =
                button.getAttribute("data-theme-choice");

            if (buttonTheme === theme) {

                button.classList.add("active");

                button.setAttribute(
                    "aria-pressed",
                    "true"
                );

            } else {

                button.classList.remove("active");

                button.setAttribute(
                    "aria-pressed",
                    "false"
                );
            }
        });
    }


    /* -------------------------------------------------------
       Initialisation
       ------------------------------------------------------- */

    function initTheme() {

        const currentTheme = getSavedTheme();

        applyTheme(currentTheme);


        const buttons = document.querySelectorAll(
            "[data-theme-choice]"
        );


        buttons.forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const selectedTheme =
                        this.getAttribute(
                            "data-theme-choice"
                        );

                    saveTheme(selectedTheme);
                }
            );

        });

    }


    /* -------------------------------------------------------
       Appliquer immédiatement le thème
       ------------------------------------------------------- */

    applyTheme(getSavedTheme());


    /* -------------------------------------------------------
       Lancer l'initialisation
       ------------------------------------------------------- */

    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            initTheme
        );

    } else {

        initTheme();

    }


    /* -------------------------------------------------------
       API ProjectFlow
       ------------------------------------------------------- */

    window.ProjectFlowTheme = {

        get: getSavedTheme,

        apply: applyTheme,

        save: saveTheme

    };


})();
