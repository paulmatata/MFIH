document.addEventListener("DOMContentLoaded", () => {

    /* =================================================
       HERO LOAD
    ================================================= */

    const hero = document.querySelector(".hero");

    if (hero) {
        hero.classList.add("loaded");
    }


    /* =================================================
       SCROLL REVEAL
    ================================================= */

    const revealElements =
        document.querySelectorAll(".reveal-on-scroll");


    if (!revealElements.length) {
        return;
    }


    const revealObserver =
        new IntersectionObserver(
            (entries, observer) => {

                entries.forEach((entry) => {

                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add("visible");

                    observer.unobserve(entry.target);

                });

            },
            {
                threshold: 0.15
            }
        );


    revealElements.forEach((element) => {

        revealObserver.observe(element);

    });

});