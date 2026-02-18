document.addEventListener('DOMContentLoaded', () => {
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    
    confirmLinks.forEach(link => {
        link.addEventListener('click', (e) => {
           
            if (!confirm(link.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});