/**
 * CSE311L Group 4: Banking Management System Frontend Script
 */

document.addEventListener('DOMContentLoaded', function() {
    // Copy code button handler
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                const text = targetEl.innerText || targetEl.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    const originalText = this.innerText;
                    this.innerText = 'Copied!';
                    this.style.backgroundColor = '#10b981';
                    setTimeout(() => {
                        this.innerText = originalText;
                        this.style.backgroundColor = '';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        });
    });

    // Auto-scroll to query result if parameter present
    if (window.location.search.includes('q=')) {
        const queryCard = document.querySelector('.query-display-card');
        if (queryCard && window.innerWidth < 900) {
            queryCard.scrollIntoView({ behavior: 'smooth' });
        }
    }
});
