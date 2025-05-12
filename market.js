// Improved market.js for dynamic dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dashboard components
    Dashboard.init();
});

// Dashboard namespace to organize functionality
const Dashboard = {
    // Initialize all dashboard components
    init: function() {
        this.addWelcomeBanner();
        this.initializeInteractions();
        this.initializeMobileNav();
        this.initializeSearch();
        this.initializeInventorySummary();
        
        // Set up auto-refresh for critical dashboard elements
        this.setupPeriodicUpdates();
    },
    
    // Add welcome banner to dashboard
    addWelcomeBanner: function() {
        const contentArea = document.querySelector('.content-area');
        
        // Create welcome banner if it doesn't exist yet
        if (!document.querySelector('.dashboard-welcome')) {
            const welcomeSection = document.createElement('div');
            welcomeSection.className = 'dashboard-welcome';
            
            // Fetch pending tasks
            this.fetchData('api/supermarket.php')
                .then(data => {
                    welcomeSection.innerHTML = `
                        <div class="welcome-content">
                            <h2>Welcome to the Grocery Store Dashboard</h2>
                            <p>Get a quick overview of your store's performance and inventory status. 
                            You have ${data.pending_orders} pending orders and ${data.unread_messages} new messages.</p>
                        </div>
                    `;
                    
                    // Insert welcome banner at the beginning of content area
                    if (contentArea && !document.querySelector('.dashboard-welcome')) {
                        contentArea.insertBefore(welcomeSection, contentArea.firstChild);
                    }
                })
                .catch(() => {
                    // Fallback to generic welcome message
                    welcomeSection.innerHTML = `
                        <div class="welcome-content">
                            <h2>Welcome to the Grocery Store Dashboard</h2>
                            <p>Get a quick overview of your store's performance and inventory status.</p>
                        </div>
                    `;
                    
                    if (contentArea && !document.querySelector('.dashboard-welcome')) {
                        contentArea.insertBefore(welcomeSection, contentArea.firstChild);
                    }
                });
        }
    },
    
    // Initialize all interactive elements
    initializeInteractions: function() {
        this.initializeProductCards();
        this.initializeWelcomeAction();
        this.initializeStatCards();
    },
    
    // Initialize product cards interactions
    initializeProductCards: function() {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            // Add hover effects
            card.addEventListener('mouseenter', function() {
                this.classList.add('card-hover');
            });
            
            card.addEventListener('mouseleave', function() {
                this.classList.remove('card-hover');
            });
            
            // Add click event for product details
            card.addEventListener('click', () => {
                const productId = card.getAttribute('data-id');
                if (productId) {
                    this.showProductDetails(productId);
                }
            });
        });
    },
    
    // Initialize welcome banner action
    initializeWelcomeAction: function() {
        const welcomeAction = document.querySelector('.welcome-action');
        
        if (welcomeAction) {
            welcomeAction.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Fetch and display alerts
                this.fetchData('api/alerts.php')
                    .then(data => {
                        this.showAlerts(data);
                    })
                    .catch(() => {
                        this.showTooltip(welcomeAction, 'Unable to load alerts');
                    });
            });
        }
    },
    
    // Initialize stat cards
    initializeStatCards: function() {
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                const statTitle = this.querySelector('.stat-title').textContent.toLowerCase();
                
                // Navigate based on stat type
                switch(statTitle) {
                    case 'total products':
                        window.location.href = 'products.php';
                        break;
                    case 'sales today':
                        window.location.href = 'sales_report.php?period=today';
                        break;
                    case 'low stock items':
                        window.location.href = 'products.php?filter=low_stock';
                        break;
                    default:
                        window.location.href = 'reports.php';
                }
            });
        });
    },
    
    // Initialize mobile navigation
    initializeMobileNav: function() {
        const mobileToggle = document.getElementById('mobileNavToggle');
        const sideNav = document.querySelector('.side-nav');
        const navOverlay = document.getElementById('navOverlay');
        
        if (mobileToggle && sideNav && navOverlay) {
            mobileToggle.addEventListener('click', function() {
                sideNav.classList.toggle('open');
                navOverlay.classList.toggle('open');
            });
            
            navOverlay.addEventListener('click', function() {
                sideNav.classList.remove('open');
                navOverlay.classList.remove('open');
            });
        }
    },
    
  
    // Initialize search functionality
    initializeSearch: function() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        
        if (searchInput) {
            // Add debouncing to reduce API calls while typing
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                
                const query = this.value.trim();
                
                // Clear results if query is empty
                if (query === '') {
                    if (searchResults) {
                        searchResults.innerHTML = '';
                        searchResults.classList.remove('show');
                    }
                    return;
                }
                
                // Set timeout to reduce API calls
                searchTimeout = setTimeout(() => {
                    Dashboard.searchProducts(query);
                }, 300);
            });
            
            // Close search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#searchInput') && !e.target.closest('#searchResults')) {
                    if (searchResults) {
                        searchResults.classList.remove('show');
                    }
                }
            });
        }
    },
    
    // Search products based on query
    searchProducts: function(query) {
        const searchResults = document.getElementById('searchResults');
        
        if (searchResults) {
            // Show loading indicator
            searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
            searchResults.classList.add('show');
            
            // Fetch search results
            this.fetchData(`api/search.php?q=${encodeURIComponent(query)}`)
                .then(data => {
                    if (data.length > 0) {
                        let resultsHTML = '';
                        
                        data.forEach(item => {
                            resultsHTML += `
                                <div class="search-result-item" data-id="${item.id}">
                                    <div class="search-result-image">
                                        <img src="${item.image_path || 'images/placeholder.jpg'}" alt="${item.name}">
                                    </div>
                                    <div class="search-result-info">
                                        <h4>${item.name}</h4>
                                        <p>${item.category}</p>
                                        <span class="search-result-price">${item.price} rwf</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        searchResults.innerHTML = resultsHTML;
                        
                        // Add click handler for results
                        document.querySelectorAll('.search-result-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const productId = this.getAttribute('data-id');
                                if (productId) {
                                    Dashboard.showProductDetails(productId);
                                    
                                    // Clear and hide search results
                                    searchResults.innerHTML = '';
                                    searchResults.classList.remove('show');
                                }
                            });
                        });
                    } else {
                        searchResults.innerHTML = '<div class="no-results">No products found</div>';
                    }
                })
                .catch(() => {
                    searchResults.innerHTML = '<div class="search-error">Error searching products</div>';
                });
        }
    },
    
    // Initialize inventory summary
    initializeInventorySummary: function() {
        const summaryContainer = document.querySelector('.inventory-summary');
        
        if (summaryContainer) {
            // Initial load
            this.loadInventorySummary();
            
            // Refresh every 5 minutes
            setInterval(() => this.loadInventorySummary(), 300000);
        }
    },
    
    // Load inventory summary data
    loadInventorySummary: function() {
        const summaryContainer = document.querySelector('.inventory-summary');
        
        if (summaryContainer) {
            this.fetchData('api/inventory_summary.php')
                .then(data => {
                    summaryContainer.innerHTML = `
                        <div class="summary-card">
                            <div class="summary-icon stock-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path>
                                    <path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path>
                                    <path d="M18 12a2 2 0 0 0 0 4h4v-4h-4z"></path>
                                </svg>
                            </div>
                            <div class="summary-details">
                                <div class="summary-title">Total Inventory Value</div>
                                <div class="summary-value">${data.total_value} rwf</div>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon critical-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <div class="summary-details">
                                <div class="summary-title">Critical Stock</div>
                                <div class="summary-value">${data.critical_stock} items</div>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon popular-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                    <polyline points="17 6 23 6 23 12"></polyline>
                                </svg>
                            </div>
                            <div class="summary-details">
                                <div class="summary-title">Popular Items</div>
                                <div class="summary-value">${data.popular_items} sales</div>
                            </div>
                        </div>
                    `;
                    
                    // Add click handler for summary cards
                    document.querySelectorAll('.summary-card').forEach((card, index) => {
                        card.addEventListener('click', function() {
                            switch(index) {
                                case 0: // Total inventory value
                                    window.location.href = 'products.php';
                                    break;
                                case 1: // Critical stock
                                    window.location.href = 'products.php?filter=critical';
                                    break;
                                case 2: // Popular items
                                    window.location.href = 'products.php?filter=popular';
                                    break;
                            }
                        });
                    });
                })
                .catch(() => {
                    summaryContainer.innerHTML = '<div class="summary-error">Error loading inventory data</div>';
                });
        }
    },
    
    // Set up periodic updates for dashboard
    setupPeriodicUpdates: function() {
        // Update dashboard stats every minute
        setInterval(() => this.updateDashboardStats(), 60000);
        
        // Check for critical updates every 5 minutes
        setInterval(() => {
            
            // Check for critical inventory status
            this.fetchData('api/critical_status.php')
                .then(data => {
                    if (data.has_critical) {
                        // Flash notification or indicator
                        const criticalIndicator = document.querySelector('.critical-indicator');
                        if (criticalIndicator) {
                            criticalIndicator.classList.add('flash');
                            setTimeout(() => {
                                criticalIndicator.classList.remove('flash');
                            }, 2000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking critical status:', error);
                });
        }, 300000);
    },
    
    // Update dashboard statistics
    updateDashboardStats: function() {
        this.fetchData('api/supermarket.php')
            .then(data => {
                // Update statistics
                document.querySelectorAll('.stat-card').forEach(card => {
                    const title = card.querySelector('.stat-title').textContent.toLowerCase().replace(/\s+/g, '_');
                    const valueElement = card.querySelector('.stat-value');
                    
                    if (data[title] !== undefined && valueElement) {
                        valueElement.textContent = data[title];
                    }
                });
                
                // Update notification count
                const notificationCount = document.querySelector('.notification-count');
                if (notificationCount) {
                    notificationCount.textContent = data.notification_count || 0;
                }
            })
            .catch(error => {
                console.error('Error updating dashboard stats:', error);
            });
    },
    
   
    // Show product details modal
    showProductDetails: function(productId) {
        // Fetch product details
        this.fetchData(`api/product_details.php?id=${productId}`)
            .then(product => {
                // Create modal with product details
                const modalContent = `
                    <div class="modal-header">
                        <h3>${product.name}</h3>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="product-details">
                        <div class="product-image">
                            <img src="${product.image_path || 'images/placeholder.jpg'}" alt="${product.name}">
                        </div>
                        <div class="product-info">
                            <p><strong>Category:</strong> ${product.category}</p>
                            <p><strong>Price:</strong> ${product.price} rwf</p>
                            <p><strong>Current Stock:</strong> ${product.quantity} units</p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge ${product.quantity <= 0 ? 'out-of-stock' : 
                                                         product.quantity <= product.reorder_level ? 'low-stock' : 
                                                         'in-stock'}">
                                    ${product.quantity <= 0 ? 'Out of Stock' : 
                                      product.quantity <= product.reorder_level ? 'Low Stock' : 
                                      'In Stock'}
                                </span>
                            </p>
                            <p><strong>Description:</strong> ${product.description || 'No description available'}</p>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="action-btn update-stock">Update Stock</button>
                        <button class="action-btn view-history">View History</button>
                    </div>
                `;
                
                // Display the modal
                const modal = this.showModal('product-modal', modalContent);
                
                // Add event listeners for action buttons
                const updateStockBtn = modal.querySelector('.update-stock');
                if (updateStockBtn) {
                    updateStockBtn.addEventListener('click', () => {
                        window.location.href = `update_stock.php?id=${productId}`;
                    });
                }
                
                const viewHistoryBtn = modal.querySelector('.view-history');
                if (viewHistoryBtn) {
                    viewHistoryBtn.addEventListener('click', () => {
                        window.location.href = `product_history.php?id=${productId}`;
                    });
                }
            })
            .catch(() => {
                this.showTooltip(document.querySelector(`.product-card[data-id="${productId}"]`), 'Error loading product details');
            });
    },
    
    // Show alerts modal
   
    // Get alert icon based on type
    getAlertIcon: function(type) {
        switch(type) {
            case 'stock':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34"></path><path d="M14 3v4a2 2 0 0 0 2 2h4"></path><path d="M16 16L22 10"></path></svg>';
            case 'order':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>';
            case 'system':
            default:
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        }
    },
    
    // Generic modal creation and display function
    showModal: function(modalClass, contentHTML) {
        // Create modal element
        const modal = document.createElement('div');
        modal.className = `modal ${modalClass}`;
        
        // Add content to modal
        modal.innerHTML = `<div class="modal-content">${contentHTML}</div>`;
        
        // Add to body
        document.body.appendChild(modal);
        
        // Show modal with slight delay for animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // Set up close button functionality
        const closeButton = modal.querySelector('.close-modal');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                this.closeModal(modal);
            });
        }
        
        // Close when clicking outside modal content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                Dashboard.closeModal(modal);
            }
        });
        
        return modal;
    },
    
    // Close modal with animation
    closeModal: function(modal) {
        modal.classList.remove('show');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    },
    
    // Show tooltip on element
    showTooltip: function(element, message) {
        if (!element) return;
        
        // Create tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'action-tooltip';
        tooltip.textContent = message;
        
        // Position and add tooltip to element
        element.appendChild(tooltip);
        
        // Show tooltip with delay for animation
        setTimeout(() => {
            tooltip.classList.add('tooltip-visible');
        }, 10);
        
        // Hide and remove tooltip after delay
        setTimeout(() => {
            tooltip.classList.remove('tooltip-visible');
            
            // Remove from DOM after fade-out transition
            setTimeout(() => {
                if (tooltip.parentNode === element) {
                    element.removeChild(tooltip);
                }
            }, 300);
        }, 3000);
    },
    
    // Generic fetch wrapper with error handling
    fetchData: function(url, options = {}) {
        return fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error(`Error fetching data from ${url}:`, error);
                throw error; // Re-throw to allow caller to handle
            });
    }
};