// FAQ page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Category filtering
    const categoryBtns = document.querySelectorAll('.category-btn');
    const faqCategories = document.querySelectorAll('.faq-category');

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.getAttribute('data-category');

            // Update active button
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter categories
            if (category === 'all') {
                faqCategories.forEach(cat => {
                    cat.classList.remove('hidden');
                    cat.style.display = 'block';
                });
            } else {
                faqCategories.forEach(cat => {
                    if (cat.getAttribute('data-category') === category) {
                        cat.classList.remove('hidden');
                        cat.style.display = 'block';
                    } else {
                        cat.classList.add('hidden');
                        cat.style.display = 'none';
                    }
                });
            }

            // Scroll to FAQ section
            const faqSection = document.querySelector('.faq-section');
            if (faqSection) {
                window.scrollTo({
                    top: faqSection.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Search functionality
    const searchInput = document.getElementById('faqSearch');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.toLowerCase().trim();

            searchTimeout = setTimeout(() => {
                performSearch(searchTerm);
            }, 300);
        });
    }

    function performSearch(searchTerm) {
        const allFaqItems = document.querySelectorAll('.faq-item');
        let hasResults = false;

        if (searchTerm === '') {
            // Reset to show all
            allFaqItems.forEach(item => {
                item.style.display = 'block';
                removeHighlights(item);
            });
            faqCategories.forEach(cat => {
                cat.style.display = 'block';
            });
            // Activate "All Questions" button
            categoryBtns.forEach(btn => {
                if (btn.getAttribute('data-category') === 'all') {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            return;
        }

        // Search through FAQ items
        allFaqItems.forEach(item => {
            const button = item.querySelector('.accordion-button');
            const body = item.querySelector('.accordion-body');
            const buttonText = button.textContent.toLowerCase();
            const bodyText = body.textContent.toLowerCase();

            if (buttonText.includes(searchTerm) || bodyText.includes(searchTerm)) {
                item.style.display = 'block';
                item.closest('.faq-category').style.display = 'block';
                hasResults = true;

                // Highlight search term
                highlightSearchTerm(button, searchTerm);
                highlightSearchTerm(body, searchTerm);
            } else {
                item.style.display = 'none';
                removeHighlights(item);
            }
        });

        // Hide empty categories
        faqCategories.forEach(cat => {
            const visibleItems = cat.querySelectorAll('.faq-item[style="display: block;"]');
            if (visibleItems.length === 0) {
                cat.style.display = 'none';
            }
        });

        // Show no results message
        showNoResultsMessage(!hasResults, searchTerm);
    }

    function highlightSearchTerm(element, searchTerm) {
        // Remove existing highlights first
        removeHighlights(element);

        const originalHTML = element.innerHTML;
        const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
        const highlightedHTML = originalHTML.replace(regex, '<span class="highlight">$1</span>');
        element.innerHTML = highlightedHTML;
    }

    function removeHighlights(container) {
        const highlights = container.querySelectorAll('.highlight');
        highlights.forEach(highlight => {
            const text = highlight.textContent;
            highlight.replaceWith(text);
        });
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function showNoResultsMessage(show, searchTerm) {
        // Remove existing message
        const existingMessage = document.querySelector('.no-results');
        if (existingMessage) {
            existingMessage.remove();
        }

        if (show) {
            const faqSection = document.querySelector('.faq-section .container .row .col-lg-8');
            const noResultsDiv = document.createElement('div');
            noResultsDiv.className = 'no-results';
            noResultsDiv.innerHTML = `
                <i class="fas fa-search"></i>
                <h4>No Results Found</h4>
                <p>We couldn't find any FAQs matching "<strong>${escapeHTML(searchTerm)}</strong>"</p>
                <p class="text-muted mt-3">Try different keywords or browse our categories above.</p>
                <button class="btn btn-primary mt-3" onclick="clearSearch()">
                    <i class="fas fa-times me-2"></i>Clear Search
                </button>
            `;
            faqSection.appendChild(noResultsDiv);
        }
    }

    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Global function to clear search
    window.clearSearch = function() {
        searchInput.value = '';
        performSearch('');
        searchInput.focus();
    };

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Auto-expand accordion if URL has hash
    const hash = window.location.hash;
    if (hash) {
        const targetCollapse = document.querySelector(hash);
        if (targetCollapse) {
            setTimeout(() => {
                const bsCollapse = new bootstrap.Collapse(targetCollapse, {
                    show: true
                });
                window.scrollTo({
                    top: targetCollapse.offsetTop - 100,
                    behavior: 'smooth'
                });
            }, 500);
        }
    }

    // Track accordion interactions
    const accordionButtons = document.querySelectorAll('.accordion-button');
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Close all other accordions in same category (optional single-open behavior)
            // Uncomment below to enable single-open per category
            /*
            const parent = this.closest('.accordion');
            const allButtons = parent.querySelectorAll('.accordion-button');
            allButtons.forEach(btn => {
                if (btn !== this && !btn.classList.contains('collapsed')) {
                    btn.click();
                }
            });
            */
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Focus search on Ctrl/Cmd + K
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }

        // Clear search on Escape
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            if (searchInput.value !== '') {
                clearSearch();
            } else {
                searchInput.blur();
            }
        }
    });

    // Add keyboard shortcut hint
    const searchBox = document.querySelector('.search-box');
    if (searchBox) {
        const hint = document.createElement('div');
        hint.className = 'text-center mt-2 text-muted';
        hint.style.fontSize = '0.85rem';
        hint.innerHTML = '<i class="fas fa-keyboard me-1"></i> Press Ctrl+K to search';
        searchBox.appendChild(hint);
    }
});
