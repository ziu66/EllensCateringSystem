// API Configuration
const API_BASE = '../api/';

// Check authentication
function checkAuth() {
    fetch(API_BASE + 'auth/check.php', {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success && window.location.pathname.includes('dashboard')) {
                window.location.href = '../../../login_dashboard.php';
            } else if (data.success && data.data) {
                document.getElementById('userEmail').textContent = data.data.email;
            }
        })
        .catch(error => {
            console.error('Auth check error:', error);
            if (window.location.pathname.includes('dashboard')) {
                window.location.href = '../../../login_dashboard.php';
            }
        });
}




// Logout function
function logout() {
    fetch(API_BASE + 'auth/logout.php', {
            method: 'POST',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '../../../login_dashboard.php';
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            window.location.href = '../../../login_dashboard.php';
        });
}

// ===== MODAL SUBMIT FUNCTIONS =====

// Submit new booking - COMPLETE FIXED VERSION
// Global variables for packages and menus
let availablePackages = [];
let availableMenus = [];
let selectedPackage = null;
let selectedMenuItems = [];

// Load packages and menus when modal opens
document.addEventListener('DOMContentLoaded', function() {
    const bookingModal = document.getElementById('addBookingModal');
    if (bookingModal) {
        bookingModal.addEventListener('show.bs.modal', function() {
            loadPackagesForBooking();
            loadMenusForBooking();
        });
    }
});

// Load packages for booking form
async function loadPackagesForBooking() {
    try {
        const response = await fetch(API_BASE + 'packages/index.php', {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.packages) {
            availablePackages = data.data.packages;
            displayPackageOptions();
        } else {
            document.getElementById('packageSelection').innerHTML =
                '<div class="col-12"><p class="text-muted text-center">No packages available</p></div>';
        }
    } catch (error) {
        console.error('Error loading packages:', error);
        document.getElementById('packageSelection').innerHTML =
            '<div class="col-12"><p class="text-danger text-center">Failed to load packages</p></div>';
    }
}

// Display package options
function displayPackageOptions() {
    const container = document.getElementById('packageSelection');

    if (availablePackages.length === 0) {
        container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No packages available</p></div>';
        return;
    }

    container.innerHTML = availablePackages.map(pkg => `
        <div class="col-md-6 mb-3">
            <div class="card package-card h-100" onclick="selectPackage(${pkg.PackageID})">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input package-radio" type="radio" 
                               name="package" id="package${pkg.PackageID}" 
                               value="${pkg.PackageID}">
                        <label class="form-check-label w-100" for="package${pkg.PackageID}">
                            <h6 class="mb-1">${pkg.PackageName}</h6>
                            <p class="text-muted small mb-2">${pkg.Description || 'No description'}</p>
                            <h5 class="text-primary mb-0">₱${parseFloat(pkg.PackPrice).toLocaleString()}</h5>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Load menus for booking form
async function loadMenusForBooking() {
    try {
        const response = await fetch(API_BASE + 'menus/index.php', {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.menus) {
            availableMenus = data.data.menus;
            displayMenuOptions();
        } else {
            document.getElementById('menuSelection').innerHTML =
                '<div class="col-12"><p class="text-muted text-center">No menu items available</p></div>';
        }
    } catch (error) {
        console.error('Error loading menus:', error);
        document.getElementById('menuSelection').innerHTML =
            '<div class="col-12"><p class="text-danger text-center">Failed to load menu items</p></div>';
    }
}

// Display menu options
function displayMenuOptions() {
    const container = document.getElementById('menuSelection');

    if (availableMenus.length === 0) {
        container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No menu items available</p></div>';
        return;
    }

    container.innerHTML = availableMenus.map(menu => `
        <div class="col-md-6 mb-2">
            <div class="card menu-card">
                <div class="card-body py-2">
                    <div class="form-check">
                        <input class="form-check-input menu-checkbox" type="checkbox" 
                               id="menu${menu.MenuID}" value="${menu.MenuID}"
                               onchange="toggleMenuItem(${menu.MenuID}, ${menu.MenuPrice})">
                        <label class="form-check-label w-100 d-flex justify-content-between" 
                               for="menu${menu.MenuID}">
                            <span>
                                <strong>${menu.DishName}</strong>
                                <br><small class="text-muted">${menu.Description || 'No description'}</small>
                            </span>
                            <span class="text-primary"><strong>₱${parseFloat(menu.MenuPrice).toLocaleString()}</strong></span>
                        </label>
                    </div>
                    <div class="mt-2" id="menuQty${menu.MenuID}" style="display: none;">
                        <label class="form-label small">Quantity:</label>
                        <input type="number" class="form-control form-control-sm" 
                               min="1" value="1" onchange="updateMenuQuantity(${menu.MenuID}, this.value, ${menu.MenuPrice})">
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Select package
function selectPackage(packageID) {
    // Unselect previous package
    selectedPackage = null;

    // Find the package
    const pkg = availablePackages.find(p => p.PackageID === packageID);

    if (pkg) {
        selectedPackage = {
            id: pkg.PackageID,
            name: pkg.PackageName,
            price: parseFloat(pkg.PackPrice)
        };

        // Update radio button
        document.getElementById('package' + packageID).checked = true;
    }

    calculateTotal();
}

// Toggle menu item
function toggleMenuItem(menuID, price) {
    const checkbox = document.getElementById('menu' + menuID);
    const qtyDiv = document.getElementById('menuQty' + menuID);

    if (checkbox.checked) {
        // Show quantity input
        qtyDiv.style.display = 'block';

        // Add to selected items
        const menu = availableMenus.find(m => m.MenuID === menuID);
        selectedMenuItems.push({
            id: menuID,
            name: menu.DishName,
            price: parseFloat(price),
            quantity: 1
        });
    } else {
        // Hide quantity input
        qtyDiv.style.display = 'none';

        // Remove from selected items
        selectedMenuItems = selectedMenuItems.filter(item => item.id !== menuID);
    }

    calculateTotal();
}

// Update menu quantity
function updateMenuQuantity(menuID, quantity, price) {
    const item = selectedMenuItems.find(m => m.id === menuID);
    if (item) {
        item.quantity = parseInt(quantity) || 1;
        calculateTotal();
    }
}

// Calculate total price
function calculateTotal() {
    let packageTotal = 0;
    let menuTotal = 0;

    // Calculate package price
    if (selectedPackage) {
        packageTotal = selectedPackage.price;
    }

    // Calculate menu items price
    selectedMenuItems.forEach(item => {
        menuTotal += item.price * item.quantity;
    });

    const subtotal = packageTotal + menuTotal;
    const total = subtotal;

    // Update display
    document.getElementById('packagePrice').textContent = '₱' + packageTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('menuPrice').textContent = '₱' + menuTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('subtotal').textContent = '₱' + subtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('totalAmount').textContent = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Update hidden input
    document.getElementById('totalAmountInput').value = total;
    document.getElementById('selectedPackageInput').value = selectedPackage ? selectedPackage.id : '';
    document.getElementById('selectedMenusInput').value = JSON.stringify(selectedMenuItems);
}

// Submit booking with enhanced data - UPDATED VERSION
async function submitBooking() {
    const form = document.getElementById('addBookingForm');
    const formData = new FormData(form);

    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Validate that at least package or menu is selected
    if (!selectedPackage && selectedMenuItems.length === 0) {
        alert('Please select at least one package or menu item');
        return;
    }

    try {
        // Get form values
        const clientEmail = formData.get('email').trim();
        const clientName = formData.get('client_name').trim();
        const contactNumber = formData.get('contact_number').trim();
        const address = formData.get('address') ? formData.get('address').trim() : '';
        const eventLocation = formData.get('event_location').trim();

        // Step 1: Check if client exists by email
        const clientCheckResponse = await fetch(API_BASE + 'clients/index.php?email=' + encodeURIComponent(clientEmail), {
            method: 'GET',
            credentials: 'include'
        });

        const clientCheckData = await clientCheckResponse.json();
        let clientID;

        if (clientCheckData.success && clientCheckData.data.clients && clientCheckData.data.clients.length > 0) {
            clientID = clientCheckData.data.clients[0].ClientID;
            console.log('Using existing client ID:', clientID);
        } else {
            // Create new client
            console.log('Creating new client...');
            const newClientResponse = await fetch(API_BASE + 'clients/index.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: clientName,
                    email: clientEmail,
                    contact_number: contactNumber,
                    address: address
                })
            });

            const newClientData = await newClientResponse.json();

            if (!newClientData.success) {
                alert('Error creating client: ' + newClientData.message);
                return;
            }

            clientID = newClientData.data.client_id;
            console.log('New client created with ID:', clientID);
        }

        // Step 2: Create the booking
        // Step 2: Create the booking
        const bookingData = {
            client_id: clientID,
            event_type: formData.get('event_type'),
            event_date: formData.get('event_date'),
            event_location: eventLocation,
            number_of_guests: parseInt(formData.get('number_of_guests')),
            special_requests: formData.get('special_requests') || '',
            total_amount: parseFloat(document.getElementById('totalAmountInput').value),
            selected_package: selectedPackage ? selectedPackage.id : null,
            selected_menus: selectedMenuItems,
            create_quotation: true // Flag to create quotation automatically
        };

        console.log('Creating booking with data:', bookingData);

        const bookingResponse = await fetch(API_BASE + 'bookings/index.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });

        const result = await bookingResponse.json();
        console.log('Booking creation result:', result);

        if (result.success) {
            alert('Booking created successfully!');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addBookingModal'));
            if (modal) {
                modal.hide();
            }

            // Reset form and selections
            form.reset();
            selectedPackage = null;
            selectedMenuItems = [];
            calculateTotal();

            // Reload data
            if (typeof loadBookingsData === 'function') {
                loadBookingsData();
            }
            if (typeof loadDashboardData === 'function') {
                loadDashboardData();
            }
        } else {
            alert('Error creating booking: ' + result.message);
        }
    } catch (error) {
        console.error('Error in submitBooking:', error);
        alert('Failed to create booking. Please check console for details.');
    }
}

// View booking details
async function viewBooking(bookingID) {
    const modal = new bootstrap.Modal(document.getElementById('viewBookingModal'));
    modal.show();

    const content = document.getElementById('viewBookingContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading booking details...</p>
        </div>
    `;

    try {
        const response = await fetch(API_BASE + 'bookings/index.php?id=' + bookingID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.bookings && data.data.bookings.length > 0) {
            const booking = data.data.bookings[0];
            displayBookingDetails(booking);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Booking not found</div>';
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load booking details</div>';
    }
}

// Display booking details
function displayBookingDetails(booking) {
    const content = document.getElementById('viewBookingContent');

    const statusBadgeClass =
        booking.Status === 'Confirmed' ? 'success' :
        booking.Status === 'Pending' ? 'warning' :
        booking.Status === 'Completed' ? 'info' :
        'secondary';

    content.innerHTML = `
        <div class="booking-details">
            <!-- Booking Info Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h4 class="mb-1">Booking #${booking.BookingID}</h4>
                    <p class="text-muted mb-0">Created on ${new Date(booking.CreatedAt).toLocaleDateString()}</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-${statusBadgeClass} fs-6">${booking.Status}</span>
                </div>
            </div>
            
            <!-- Client Information -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-fill me-2"></i>Client Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> ${booking.ClientName || 'N/A'}</p>
                            <p class="mb-2"><strong>Email:</strong> ${booking.ClientEmail || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Contact:</strong> ${booking.ContactNumber || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Details -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Event Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Event Type:</strong> ${booking.EventType}</p>
                            <p class="mb-2"><strong>Event Date:</strong> ${booking.EventDate}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Location:</strong> ${booking.EventLocation || 'TBD'}</p>
                            <p class="mb-2"><strong>Number of Guests:</strong> ${booking.NumberOfGuests}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Special Requests -->
            ${booking.SpecialRequests ? `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Special Requests</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">${booking.SpecialRequests}</p>
                </div>
            </div>
            ` : ''}
            
            <!-- Pricing Information -->
            <div class="card border-dark">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Pricing Information</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Total Amount:</h5>
                        <h3 class="mb-0 text-primary">₱${parseFloat(booking.TotalAmount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</h3>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Print booking details
function printBookingDetails() {
    const content = document.getElementById('viewBookingContent').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Booking Details</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<style>body { padding: 20px; } .badge { display: inline-block; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Call this on page load to verify API is working
document.addEventListener('DOMContentLoaded', function() {
    console.log('Testing API connection...');
    testAPIConnection();
});



// Submit new menu
async function submitMenu() {
    const form = document.getElementById('addMenuForm');
    const formData = new FormData(form);

    const data = {
        dish_name: formData.get('dish_name'),
        description: formData.get('description'),
        menu_price: formData.get('menu_price'),
        image_url: formData.get('image_url'),
        category: formData.get('category')
    };

    try {
        const response = await fetch(API_BASE + 'menus/index.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Menu item added successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addMenuModal')).hide();
            form.reset();
            loadMenusData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error adding menu:', error);
        alert('Failed to add menu item');
    }
}
// Submit new client
async function submitClient() {
    const form = document.getElementById('addClientForm');
    const formData = new FormData(form);

    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        contact_number: formData.get('contact_number'),
        address: formData.get('address')
    };

    try {
        const response = await fetch(API_BASE + 'clients/index.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Client added successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addClientModal')).hide();
            form.reset();
            loadClientsData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error adding client:', error);
        alert('Failed to add client');
    }
}

// Page Templates
const pages = {
    dashboard: `
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Bookings</p>
                                <h3 class="mb-0" id="statTotalBookings">...</h3>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> <span id="statBookingsChange">...</span></small>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-calendar-check fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Revenue</p>
                                <h3 class="mb-0" id="statRevenue">...</h3>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> <span id="statRevenueChange">...</span></small>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-cash-coin fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Active Clients</p>
                                <h3 class="mb-0" id="statClients">...</h3>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> <span id="statClientsChange">...</span></small>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-people fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
               

       

            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Pending Quotes</p>
                                <h3 class="mb-0" id="statQuotes">...</h3>
                                <small class="text-muted"><i class="bi bi-dash"></i> <span id="statQuotesChange">...</span></small>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-8 mb-3">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Revenue Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Upcoming Events</h5>
                    </div>
                    <div class="card-body" id="upcomingEvents">
                        <p class="text-muted">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bookings</h5>
                        <a href="#" class="text-white" onclick="loadPage('bookings'); return false;">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Guests</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="recentBookingsTable">
                                    <tr><td colspan="7" class="text-center">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,

    bookings: `
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">All Bookings</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search bookings..." id="searchBookings" onkeyup="filterBookings()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus" onchange="filterBookings()">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Guests</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTable">
                            <tr><td colspan="8" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

    quotations: `
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Quotations</h5>
        </div>
        <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="quotationsTable">
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

    menus: `
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-book display-4 mb-2"></i>
                        <h3 class="mb-0" id="totalMenus">...</h3>
                        <p class="text-muted mb-0">Total Menus</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-egg-fried display-4 mb-2"></i>
                        <h3 class="mb-0" id="totalDishes">...</h3>
                        <p class="text-muted mb-0">Total Dishes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-currency-dollar display-4 mb-2"></i>
                        <h3 class="mb-0" id="avgPrice">...</h3>
                        <p class="text-muted mb-0">Avg. Price</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Menu Catalog</h5>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Menu
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dish Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="menusTable">
                            <tr><td colspan="4" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

    clients: `
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Client Management</h5>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Client
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Search clients..." id="searchClients" onkeyup="filterClients()">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Events</th>
                                <th>Total Spent</th>
                                <th>Last Event</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTable">
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

      packages: `
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-box-seam display-4 mb-2"></i>
                        <h3 class="mb-0" id="totalPackages">...</h3>
                        <p class="text-muted mb-0">Total Packages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-check-circle display-4 mb-2"></i>
                        <h3 class="mb-0" id="activePackages">...</h3>
                        <p class="text-muted mb-0">Active Packages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-currency-dollar display-4 mb-2"></i>
                        <h3 class="mb-0" id="avgPackagePrice">...</h3>
                        <p class="text-muted mb-0">Avg. Price</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Packages</h5>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Package
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="packagesTable">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

       services: `
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-star display-4 mb-2"></i>
                        <h3 class="mb-0" id="totalServices">...</h3>
                        <p class="text-muted mb-0">Total Services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-check-circle display-4 mb-2"></i>
                        <h3 class="mb-0" id="activeServices">...</h3>
                        <p class="text-muted mb-0">Available Services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="bi bi-currency-dollar display-4 mb-2"></i>
                        <h3 class="mb-0" id="avgServicePrice">...</h3>
                        <p class="text-muted mb-0">Avg. Price</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Services</h5>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Service
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="servicesTable">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

    sales: `
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Revenue</p>
                        <h3 class="mb-0" id="totalRevenue">...</h3>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> <span id="revenueChange">...</span></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Pending Payments</p>
                        <h3 class="mb-0" id="pendingPayments">...</h3>
                        <small class="text-muted" id="pendingCount">...</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <p class="text-muted mb-1">This Month</p>
                        <h3 class="mb-0" id="monthRevenue">...</h3>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> <span id="monthChange">...</span></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Completed</p>
                        <h3 class="mb-0" id="completedBookings">...</h3>
                        <small class="text-muted" id="completedCount">...</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Client</th>
                                <th>Event</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTable">
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,

    reports: `
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Generate Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Report Type</label>
                                <select class="form-select">
                                    <option>Sales Report</option>
                                    <option>Bookings Report</option>
                                    <option>Client Report</option>
                                    <option>Revenue Report</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-dark w-100">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Bookings by Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesCategoryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Monthly Performance</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `,

    notifications: `
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Notifications</h5>
                <button class="btn btn-light btn-sm">
                    <i class="bi bi-check-all me-1"></i>Mark All Read
                </button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="notificationsList">
                    <div class="list-group-item text-center text-muted">No notifications</div>
                </div>
            </div>
        </div>
    `
};

// Load page content
function loadPage(pageName) {
    const pageContent = document.getElementById('pageContent');
    const pageTitle = document.getElementById('pageTitle');

    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    const activeLink = document.querySelector(`[data-page="${pageName}"]`);
    if (activeLink) activeLink.classList.add('active');

    // Update page title
    const titles = {
        dashboard: 'Dashboard',
        bookings: 'Bookings',
        quotations: 'Quotations',
        menus: 'Menus',
        clients: 'Clients',
        packages: 'Packages',
        services: 'Services',
        sales: 'Sales & Payments',
        reports: 'Reports',
        notifications: 'Notifications'
    };
    pageTitle.textContent = titles[pageName];

    // Load page content
    pageContent.innerHTML = pages[pageName];

    // Load page-specific data
    switch (pageName) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'bookings':
            loadBookingsData();
            break;
        case 'quotations':
            loadQuotationsData();
            break;
        case 'menus':
            loadMenusData();
            break;
        case 'clients':
            loadClientsData();
            break;
        case 'packages':
            loadPackagesData();
            break;
        case 'services':
            loadServicesData();
            break;
        case 'sales':
            loadSalesData();
            break;
        case 'reports':
            loadReportsCharts();
            break;
        case 'notifications':
            loadNotifications();
            break;
    }
}

// ===== DASHBOARD DATA =====
async function loadDashboardData() {
    try {
        const [bookingsRes, clientsRes, quotationsRes] = await Promise.all([
            fetch(API_BASE + 'bookings/index.php?limit=5', { credentials: 'include' }),
            fetch(API_BASE + 'clients/index.php', { credentials: 'include' }),
            fetch(API_BASE + 'quotations/index.php?status=Pending', { credentials: 'include' })
        ]);

        const bookingsData = await bookingsRes.json();
        const clientsData = await clientsRes.json();
        const quotationsData = await quotationsRes.json();

        if (bookingsData.success) {
            document.getElementById('statTotalBookings').textContent = bookingsData.data.total;
            document.getElementById('statBookingsChange').textContent = '12% this month';

            const totalRevenue = bookingsData.data.bookings.reduce((sum, b) => sum + parseFloat(b.TotalAmount || 0), 0);
            document.getElementById('statRevenue').textContent = '₱' + totalRevenue.toLocaleString();
            document.getElementById('statRevenueChange').textContent = '8% this month';

            loadRecentBookingsTable(bookingsData.data.bookings);
            loadUpcomingEvents(bookingsData.data.bookings);
        }

        if (clientsData.success) {
            document.getElementById('statClients').textContent = clientsData.data.total;
            document.getElementById('statClientsChange').textContent = '5% this month';
        }

        if (quotationsData.success) {
            document.getElementById('statQuotes').textContent = quotationsData.data.total;
            document.getElementById('statQuotesChange').textContent = 'No change';
        }

        createRevenueChart();

    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function loadRecentBookingsTable(bookings) {
    const tbody = document.getElementById('recentBookingsTable');
    if (!bookings || bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No bookings found</td></tr>';
        return;
    }

    tbody.innerHTML = bookings.map(booking => `
        <tr>
            <td>#${booking.BookingID}</td>
            <td>${booking.ClientName || 'N/A'}</td>
            <td>${booking.EventType}</td>
            <td>${booking.EventDate}</td>
            <td>${booking.NumberOfGuests}</td>
            <td><span class="badge badge-${booking.Status.toLowerCase()}">${booking.Status}</span></td>
            <td>₱${parseFloat(booking.TotalAmount || 0).toLocaleString()}</td>
        </tr>
    `).join('');
}

function loadUpcomingEvents(bookings) {
    const container = document.getElementById('upcomingEvents');
    const upcoming = bookings.filter(b => new Date(b.EventDate) > new Date()).slice(0, 3);

    if (upcoming.length === 0) {
        container.innerHTML = '<p class="text-muted">No upcoming events</p>';
        return;
    }

    container.innerHTML = `
        <div class="list-group list-group-flush">
            ${upcoming.map(booking => `
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${booking.EventType}</h6>
                            <small class="text-muted">${booking.EventDate} - ${booking.NumberOfGuests} guests</small>
                        </div>
                        <span class="badge bg-dark">Soon</span>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function createRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, 32000, 45000],
                    borderColor: '#000',
                    backgroundColor: 'rgba(0, 0, 0, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
}

// ===== BOOKINGS DATA =====
let allBookings = [];

async function loadBookingsData() {
    try {
        const response = await fetch(API_BASE + 'bookings/index.php', { credentials: 'include' });
        const data = await response.json();

        if (data.success) {
            allBookings = data.data.bookings;
            displayBookings(allBookings);
        } else {
            document.getElementById('bookingsTable').innerHTML = 
                '<tr><td colspan="8" class="text-center text-danger">Error loading bookings</td></tr>';
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
        document.getElementById('bookingsTable').innerHTML = 
            '<tr><td colspan="8" class="text-center text-danger">Failed to load bookings</td></tr>';
    }
}

function displayBookings(bookings) {
    const tbody = document.getElementById('bookingsTable');
    
    if (!bookings || bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No bookings found</td></tr>';
        return;
    }

    tbody.innerHTML = bookings.map(booking => `
        <tr>
            <td>#${booking.BookingID}</td>
            <td>${booking.ClientName || 'N/A'}</td>
            <td>${booking.EventType}</td>
            <td>${booking.EventDate}</td>
            <td>${booking.NumberOfGuests}</td>
            <td><span class="badge badge-${booking.Status.toLowerCase()}">${booking.Status}</span></td>
            <td>₱${parseFloat(booking.TotalAmount || 0).toLocaleString()}</td>
            <td>
                <button class="btn btn-sm btn-outline-dark" onclick="viewBookingDetails(${booking.BookingID})" title="View Details"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-outline-dark" onclick="editBookingDetails(${booking.BookingID})" title="Edit Booking"><i class="bi bi-pencil"></i></button>
            </td>
        </tr>
    `).join('');
}

function filterBookings() {
    const searchTerm = document.getElementById('searchBookings').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    
    let filtered = allBookings;
    
    if (searchTerm) {
        filtered = filtered.filter(b => 
            b.ClientName.toLowerCase().includes(searchTerm) ||
            b.EventType.toLowerCase().includes(searchTerm)
        );
    }
    
    if (statusFilter) {
        filtered = filtered.filter(b => b.Status === statusFilter);
    }
    
    displayBookings(filtered);
}

// ===== QUOTATIONS DATA =====
async function loadQuotationsData() {
    try {
        const response = await fetch(API_BASE + 'quotations/index.php', { credentials: 'include' });
        const data = await response.json();

        const tbody = document.getElementById('quotationsTable');
        
        if (data.success && data.data.quotations.length > 0) {
            tbody.innerHTML = data.data.quotations.map(quote => `
                <tr>
                    <td>#Q${quote.QuotationID}</td>
                    <td>${quote.ClientName || 'N/A'}</td>
                    <td>${quote.EventType}</td>
                    <td>${quote.EventDate}</td>
                    <td>₱${parseFloat(quote.EstimatedPrice).toLocaleString()}</td>
                    <td><span class="badge bg-${quote.Status === 'Approved' ? 'success' : quote.Status === 'Pending' ? 'warning' : 'secondary'}">${quote.Status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-dark" onclick="viewQuotation(${quote.QuotationID})"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-dark" onclick="approveQuotation(${quote.QuotationID})"><i class="bi bi-check-circle"></i></button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No quotations found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading quotations:', error);
        document.getElementById('quotationsTable').innerHTML = 
            '<tr><td colspan="7" class="text-center text-danger">Failed to load quotations</td></tr>';
    }
}

// ===== MENUS DATA =====
async function loadMenusData() {
    try {
        const response = await fetch(API_BASE + 'menus/index.php', { credentials: 'include' });
        const data = await response.json();

        if (data.success) {
            const menus = data.data.menus;
            
            document.getElementById('totalMenus').textContent = data.data.total;
            document.getElementById('totalDishes').textContent = data.data.total;
            
            const avgPrice = menus.length > 0 ? menus.reduce((sum, m) => sum + parseFloat(m.MenuPrice), 0) / menus.length : 0;
            document.getElementById('avgPrice').textContent = '₱' + avgPrice.toFixed(0);
            
            const tbody = document.getElementById('menusTable');
            
            if (menus.length > 0) {
               // Replace the tbody.innerHTML section with:
tbody.innerHTML = menus.map(menu => `
    <tr>
        <td>
            ${menu.ImageURL ? `<img src="${menu.ImageURL}" alt="${menu.DishName}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">` : ''}
            ${menu.DishName}
            ${menu.Category ? `<span class="badge bg-secondary ms-2">${menu.Category}</span>` : ''}
        </td>
        <td>${menu.Description || 'N/A'}</td>
        <td>
            <strong>Small:</strong> ₱${parseFloat(menu.MenuPrice).toLocaleString()}<br>
            <strong>Medium:</strong> ₱${(parseFloat(menu.MenuPrice) * 1.4).toLocaleString(undefined, {minimumFractionDigits: 2})}<br>
            <strong>Large:</strong> ₱${(parseFloat(menu.MenuPrice) * 1.95).toLocaleString(undefined, {minimumFractionDigits: 2})}
        </td>
        <td>
            <button class="btn btn-sm btn-outline-dark" onclick="editMenu(${menu.MenuID})"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteMenu(${menu.MenuID})"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
`).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No menu items found</td></tr>';
            }
        }
    } catch (error) {
        console.error('Error loading menus:', error);
        document.getElementById('menusTable').innerHTML = 
            '<tr><td colspan="4" class="text-center text-danger">Failed to load menus</td></tr>';
    }
}

// ===== CLIENTS DATA =====
let allClients = [];

async function loadClientsData() {
    try {
        const response = await fetch(API_BASE + 'clients/index.php', { credentials: 'include' });
        const data = await response.json();

        if (data.success) {
            allClients = data.data.clients;
            displayClients(allClients);
        }
    } catch (error) {
        console.error('Error loading clients:', error);
        document.getElementById('clientsTable').innerHTML = 
            '<tr><td colspan="7" class="text-center text-danger">Failed to load clients</td></tr>';
    }
}

function displayClients(clients) {
    const tbody = document.getElementById('clientsTable');
    
    if (!clients || clients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No clients found</td></tr>';
        return;
    }

    tbody.innerHTML = clients.map(client => `
        <tr>
            <td>${client.Name}</td>
            <td>${client.Email}</td>
            <td>${client.ContactNumber}</td>
            <td>${client.TotalBookings}</td>
            <td>₱${parseFloat(client.TotalSpent).toLocaleString()}</td>
            <td>${client.LastEventDate || 'N/A'}</td>
            <td>
                <button class="btn btn-sm btn-outline-dark" onclick="viewClient(${client.ClientID})"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-outline-dark" onclick="editClient(${client.ClientID})"><i class="bi bi-pencil"></i></button>
            </td>
        </tr>
    `).join('');
}

function filterClients() {
    const searchTerm = document.getElementById('searchClients').value.toLowerCase();
    
    const filtered = allClients.filter(c => 
        c.Name.toLowerCase().includes(searchTerm) ||
        c.Email.toLowerCase().includes(searchTerm) ||
        c.ContactNumber.includes(searchTerm)
    );
    
    displayClients(filtered);
}

// ===== PACKAGES DATA =====
let allPackages = [];

async function loadPackagesData() {
    try {
        const response = await fetch(API_BASE + 'packages/manage_packages.php', { credentials: 'include' });
        const data = await response.json();
        // ... rest of the function stays the same
    } catch (error) {
        console.error('Error loading packages:', error);
        document.getElementById('packagesTable').innerHTML = 
            '<tr><td colspan="5" class="text-center text-danger">Failed to load packages</td></tr>';
    }
}


// ===== SERVICES DATA =====
let allServices = [];
let serviceInclusions = [];

async function loadServicesData() {
    try {
     const response = await fetch(API_BASE + 'services/index.php', { 
    method: 'GET',
    credentials: 'include' 
});
        const data = await response.json();

        if (data.success) {
            allServices = data.data.services;
            
            // Update stats
            document.getElementById('totalServices').textContent = data.data.total;
            const activeCount = allServices.filter(s => s.IsActive == 1).length;
            document.getElementById('activeServices').textContent = activeCount;
            
            const avgPrice = allServices.length > 0 
                ? allServices.reduce((sum, s) => sum + parseFloat(s.PricePerPerson), 0) / allServices.length 
                : 0;
            document.getElementById('avgServicePrice').textContent = '₱' + avgPrice.toFixed(0);
            
            displayServices(allServices);
        } else {
            document.getElementById('servicesTable').innerHTML = 
                '<tr><td colspan="5" class="text-center text-danger">Error loading services</td></tr>';
        }
    } catch (error) {
    console.error('Error loading services:', error);
    console.error('API URL:', API_BASE + 'services/index.php');
    document.getElementById('servicesTable').innerHTML = 
        '<tr><td colspan="5" class="text-center text-danger">Failed to load services: ' + error.message + '</td></tr>';
    }
}

function displayServices(services) {
    const tbody = document.getElementById('servicesTable');
    
    if (!services || services.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No services found</td></tr>';
        return;
    }

    tbody.innerHTML = services.map(service => `
        <tr>
            <td>
                <i class="${service.IconClass} me-2"></i>
                <strong>${service.ServiceName}</strong>
                ${service.IsPopular == 1 ? '<span class="badge bg-warning text-dark ms-2">Popular</span>' : ''}
            </td>
            <td>${service.Description || 'N/A'}</td>
            <td>₱${parseFloat(service.PricePerPerson).toLocaleString()}/person</td>
            <td>
                ${service.IsActive == 1 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-secondary">Inactive</span>'}
            </td>
            <td>
                <button class="btn btn-sm btn-outline-dark" onclick="viewService(${service.ServiceID})" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-dark" onclick="editService(${service.ServiceID})" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteService(${service.ServiceID})" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Submit new service
async function submitService() {
    const form = document.getElementById('addServiceForm');
    const formData = new FormData(form);

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        service_name: formData.get('service_name'),
        service_type: formData.get('service_type'),
        price_per_person: parseFloat(formData.get('price_per_person')),
        minimum_guests: parseInt(formData.get('minimum_guests')),
        description: formData.get('description'),
        icon_class: formData.get('icon_class'),
        inclusions: document.getElementById('inclusionsData').value,
        is_popular: document.getElementById('isPopularService').checked ? 1 : 0,
        is_active: document.getElementById('isActiveService').checked ? 1 : 0,
        display_order: parseInt(formData.get('display_order'))
    };

    try {
        const response = await fetch(API_BASE + 'services/index.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Service added successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addServiceModal')).hide();
            form.reset();
            serviceInclusions = [];
            updateInclusionsList();
            loadServicesData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error adding service:', error);
        alert('Failed to add service');
    }
}

// Inclusions management
function addInclusion() {
    const input = document.getElementById('inclusionInput');
    const inclusion = input.value.trim();
    
    if (inclusion) {
        serviceInclusions.push(inclusion);
        updateInclusionsList();
        input.value = '';
    }
}

function removeInclusion(index) {
    serviceInclusions.splice(index, 1);
    updateInclusionsList();
}

function updateInclusionsList() {
    const list = document.getElementById('inclusionsList');
    const dataInput = document.getElementById('inclusionsData');
    
    if (serviceInclusions.length === 0) {
        list.innerHTML = '<small class="text-muted">No inclusions added yet</small>';
    } else {
        list.innerHTML = serviceInclusions.map((inclusion, index) => `
            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mb-2">
                <span><i class="bi bi-check-circle text-success me-2"></i>${inclusion}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeInclusion(${index})">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `).join('');
    }
    
    dataInput.value = JSON.stringify(serviceInclusions);
}

// View service details
async function viewService(serviceID) {
    const modal = new bootstrap.Modal(document.getElementById('viewEditBookingModal'));
    document.getElementById('viewEditModalTitle').textContent = 'Service Details';
    modal.show();

    const content = document.getElementById('viewEditBookingContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading service details...</p>
        </div>
    `;

    try {
        const response = await fetch(API_BASE + 'services/index.php?id=' + serviceID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.services && data.data.services.length > 0) {
            const service = data.data.services[0];
            displayServiceDetails(service);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Service not found</div>';
        }
    } catch (error) {
        console.error('Error loading service details:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load service details</div>';
    }
}

function displayServiceDetails(service) {
    const content = document.getElementById('viewEditBookingContent');
    const inclusions = JSON.parse(service.Inclusions || '[]');

    content.innerHTML = `
        <div class="service-details">
            <div class="text-center mb-4">
                <i class="${service.IconClass}" style="font-size: 4rem; color: var(--primary-dark);"></i>
                <h3 class="mt-3">${service.ServiceName}</h3>
                ${service.IsPopular == 1 ? '<span class="badge bg-warning text-dark">Popular Service</span>' : ''}
            </div>
            
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Service Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Type:</strong> ${service.ServiceType.replace('_', ' ').toUpperCase()}</p>
                            <p class="mb-2"><strong>Price:</strong> ₱${parseFloat(service.PricePerPerson).toLocaleString()}/person</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Minimum Guests:</strong> ${service.MinimumGuests}</p>
                            <p class="mb-2"><strong>Status:</strong> ${service.IsActive == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</p>
                        </div>
                    </div>
                    ${service.Description ? `<p class="mt-3 mb-0"><strong>Description:</strong><br>${service.Description}</p>` : ''}
                </div>
            </div>
            
            ${inclusions.length > 0 ? `
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-check-all me-2"></i>Inclusions</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        ${inclusions.map(inc => `<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>${inc}</li>`).join('')}
                    </ul>
                </div>
            </div>
            ` : ''}
        </div>
        
        <div class="modal-footer mt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-dark" onclick="editService(${service.ServiceID})">
                <i class="bi bi-pencil me-1"></i>Edit Service
            </button>
        </div>
    `;
}

// Edit service (simplified - will show modal with edit mode)
async function editService(serviceID) {
    try {
        const response = await fetch(API_BASE + 'services/index.php?id=' + serviceID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.services && data.data.services.length > 0) {
            const service = data.data.services[0];
            showEditServiceModal(service);
        } else {
            alert('Service not found');
        }
    } catch (error) {
        console.error('Error loading service:', error);
        alert('Failed to load service');
    }
}

function showEditServiceModal(service) {
    // Populate modal
    document.querySelector('#addServiceModal .modal-title').textContent = 'Edit Service';
    document.querySelector('#addServiceForm input[name="service_name"]').value = service.ServiceName;
    document.querySelector('#addServiceForm select[name="service_type"]').value = service.ServiceType;
    document.querySelector('#addServiceForm input[name="price_per_person"]').value = service.PricePerPerson;
    document.querySelector('#addServiceForm input[name="minimum_guests"]').value = service.MinimumGuests;
    document.querySelector('#addServiceForm textarea[name="description"]').value = service.Description || '';
    document.querySelector('#addServiceForm input[name="icon_class"]').value = service.IconClass;
    document.querySelector('#addServiceForm input[name="display_order"]').value = service.DisplayOrder;
    document.getElementById('isPopularService').checked = service.IsPopular == 1;
    document.getElementById('isActiveService').checked = service.IsActive == 1;
    
    serviceInclusions = JSON.parse(service.Inclusions || '[]');
    updateInclusionsList();
    
    // Store service ID
    document.getElementById('addServiceForm').dataset.serviceId = service.ServiceID;
    document.getElementById('addServiceForm').dataset.editMode = 'true';
    
    // Change button
    const submitBtn = document.querySelector('#addServiceModal .modal-footer .btn-dark');
    submitBtn.textContent = 'Update Service';
    submitBtn.onclick = function() { updateService(); };
    
    new bootstrap.Modal(document.getElementById('addServiceModal')).show();
}

async function updateService() {
    const form = document.getElementById('addServiceForm');
    const formData = new FormData(form);
    const serviceID = form.dataset.serviceId;

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        service_id: parseInt(serviceID),
        service_name: formData.get('service_name'),
        service_type: formData.get('service_type'),
        price_per_person: parseFloat(formData.get('price_per_person')),
        minimum_guests: parseInt(formData.get('minimum_guests')),
        description: formData.get('description'),
        icon_class: formData.get('icon_class'),
        inclusions: document.getElementById('inclusionsData').value,
        is_popular: document.getElementById('isPopularService').checked ? 1 : 0,
        is_active: document.getElementById('isActiveService').checked ? 1 : 0,
        display_order: parseInt(formData.get('display_order'))
    };

    try {
        const response = await fetch(API_BASE + 'services/index.php', {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Service updated successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addServiceModal')).hide();
            resetServiceForm();
            loadServicesData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating service:', error);
        alert('Failed to update service');
    }
}

async function deleteService(serviceID) {
    if (confirm('Are you sure you want to delete this service?')) {
        try {
            const response = await fetch(API_BASE + 'services/index.php', {
                method: 'DELETE',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ service_id: serviceID })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Service deleted successfully!');
                loadServicesData();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting service:', error);
            alert('Failed to delete service');
        }
    }
}

function resetServiceForm() {
    const form = document.getElementById('addServiceForm');
    form.reset();
    delete form.dataset.serviceId;
    delete form.dataset.editMode;
    serviceInclusions = [];
    updateInclusionsList();
    
    document.querySelector('#addServiceModal .modal-title').textContent = 'Add Service';
    const submitBtn = document.querySelector('#addServiceModal .modal-footer .btn-dark');
    submitBtn.textContent = 'Add Service';
    submitBtn.onclick = function() { submitService(); };
}

// Reset form when modal closes
document.addEventListener('DOMContentLoaded', function() {
    const serviceModal = document.getElementById('addServiceModal');
    if (serviceModal) {
        serviceModal.addEventListener('hidden.bs.modal', function() {
            if (document.getElementById('addServiceForm') && document.getElementById('addServiceForm').dataset.editMode) {
                resetServiceForm();
            }
        });
    }
    
    // Allow Enter key to add inclusions
    const inclusionInput = document.getElementById('inclusionInput');
    if (inclusionInput) {
        inclusionInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addInclusion();
            }
        });
    }
});

// ===== SALES DATA =====
async function loadSalesData() {
    try {
        const response = await fetch(API_BASE + 'bookings/index.php', { credentials: 'include' });
        const data = await response.json();

        if (data.success) {
            const bookings = data.data.bookings;
            
            const totalRevenue = bookings.reduce((sum, b) => sum + parseFloat(b.TotalAmount || 0), 0);
            const pendingBookings = bookings.filter(b => b.Status === 'Pending');
            const completedBookings = bookings.filter(b => b.Status === 'Completed');
            const pendingAmount = pendingBookings.reduce((sum, b) => sum + parseFloat(b.TotalAmount || 0), 0);
            
            document.getElementById('totalRevenue').textContent = '₱' + totalRevenue.toLocaleString();
            document.getElementById('revenueChange').textContent = '15% from last month';
            
            document.getElementById('pendingPayments').textContent = '₱' + pendingAmount.toLocaleString();
            document.getElementById('pendingCount').textContent = pendingBookings.length + ' invoices';
            
            document.getElementById('monthRevenue').textContent = '₱' + totalRevenue.toLocaleString();
            document.getElementById('monthChange').textContent = '8% increase';
            
            document.getElementById('completedBookings').textContent = completedBookings.length;
            document.getElementById('completedCount').textContent = 'bookings';
            
            const tbody = document.getElementById('transactionsTable');
            tbody.innerHTML = bookings.slice(0, 10).map(booking => `
                <tr>
                    <td>#${booking.BookingID}</td>
                    <td>${booking.ClientName || 'N/A'}</td>
                    <td>${booking.EventType}</td>
                    <td>₱${parseFloat(booking.TotalAmount || 0).toLocaleString()}</td>
                    <td><span class="badge badge-${booking.Status.toLowerCase()}">${booking.Status}</span></td>
                    <td>${booking.EventDate}</td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading sales:', error);
    }
}

// ===== REPORTS CHARTS =====
function loadReportsCharts() {
    const ctx1 = document.getElementById('salesCategoryChart');
    if (ctx1 && typeof Chart !== 'undefined') {
        fetch(API_BASE + 'bookings/index.php', { credentials: 'include' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const bookings = data.data.bookings;
                    const statusCounts = {
                        'Pending': bookings.filter(b => b.Status === 'Pending').length,
                        'Confirmed': bookings.filter(b => b.Status === 'Confirmed').length,
                        'Completed': bookings.filter(b => b.Status === 'Completed').length,
                        'Cancelled': bookings.filter(b => b.Status === 'Cancelled').length
                    };
                    
                    new Chart(ctx1, {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(statusCounts),
                            datasets: [{
                                data: Object.values(statusCounts),
                                backgroundColor: ['#6c757d', '#000', '#495057', '#adb5bd']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true
                        }
                    });
                }
            });
    }
    
    const ctx2 = document.getElementById('monthlyPerformanceChart');
    if (ctx2 && typeof Chart !== 'undefined') {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Events',
                    data: [12, 19, 15, 25, 22, 30],
                    backgroundColor: '#000'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// ===== NOTIFICATIONS =====
function loadNotifications() {
    const list = document.getElementById('notificationsList');
    list.innerHTML = '<div class="list-group-item text-center text-muted">No new notifications</div>';
}

// ===== ACTION FUNCTIONS =====
// View booking details - READ ONLY MODE
async function viewBookingDetails(bookingID) {
    const modal = new bootstrap.Modal(document.getElementById('viewEditBookingModal'));
    document.getElementById('viewEditModalTitle').textContent = 'View Booking Details';
    modal.show();

    const content = document.getElementById('viewEditBookingContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading booking details...</p>
        </div>
    `;

    try {
        const response = await fetch(API_BASE + 'bookings/index.php?id=' + bookingID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.bookings && data.data.bookings.length > 0) {
            const booking = data.data.bookings[0];
            displayBookingDetailsReadOnly(booking);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Booking not found</div>';
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load booking details</div>';
    }
}

// Display booking details in read-only mode
function displayBookingDetailsReadOnly(booking) {
    const content = document.getElementById('viewEditBookingContent');

    const statusBadgeClass =
        booking.Status === 'Confirmed' ? 'success' :
        booking.Status === 'Pending' ? 'warning' :
        booking.Status === 'Completed' ? 'info' :
        'secondary';

    content.innerHTML = `
        <div class="booking-details">
            <!-- Booking Info Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h4 class="mb-1">Booking #${booking.BookingID}</h4>
                    <p class="text-muted mb-0">Created on ${new Date(booking.CreatedAt).toLocaleDateString()}</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-${statusBadgeClass} fs-6">${booking.Status}</span>
                </div>
            </div>
            
            <!-- Client Information -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-fill me-2"></i>Client Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> ${booking.ClientName || 'N/A'}</p>
                            <p class="mb-2"><strong>Email:</strong> ${booking.ClientEmail || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Contact:</strong> ${booking.ContactNumber || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Details -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Event Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Event Type:</strong> ${booking.EventType}</p>
                            <p class="mb-2"><strong>Event Date:</strong> ${booking.EventDate}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Location:</strong> ${booking.EventLocation || 'TBD'}</p>
                            <p class="mb-2"><strong>Number of Guests:</strong> ${booking.NumberOfGuests}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Special Requests -->
            ${booking.SpecialRequests ? `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Special Requests</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">${booking.SpecialRequests}</p>
                </div>
            </div>
            ` : ''}
            
            <!-- Pricing Information -->
            <div class="card border-dark">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Pricing Information</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Total Amount:</h5>
                        <h3 class="mb-0 text-primary">₱${parseFloat(booking.TotalAmount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer mt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-dark" onclick="switchToEditMode(${booking.BookingID})">
                <i class="bi bi-pencil me-1"></i>Edit Booking
            </button>
        </div>
    `;
}

// Edit booking - EDIT MODE
async function editBookingDetails(bookingID) {
    const modal = new bootstrap.Modal(document.getElementById('viewEditBookingModal'));
    document.getElementById('viewEditModalTitle').textContent = 'Edit Booking';
    modal.show();

    const content = document.getElementById('viewEditBookingContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading booking details...</p>
        </div>
    `;

    try {
        const response = await fetch(API_BASE + 'bookings/index.php?id=' + bookingID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.bookings && data.data.bookings.length > 0) {
            const booking = data.data.bookings[0];
            displayBookingEditForm(booking);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Booking not found</div>';
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load booking details</div>';
    }
}

// Switch from view mode to edit mode
function switchToEditMode(bookingID) {
    editBookingDetails(bookingID);
}

// Display booking edit form
function displayBookingEditForm(booking) {
    const content = document.getElementById('viewEditBookingContent');

    content.innerHTML = `
        <form id="editBookingForm">
            <input type="hidden" id="editBookingID" value="${booking.BookingID}">
            
            <!-- Client Information -->
            <h6 class="border-bottom pb-2 mb-3">Client Information</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Client Name</label>
                    <input type="text" class="form-control" id="editClientName" value="${booking.ClientName || ''}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="editClientEmail" value="${booking.ClientEmail || ''}" readonly>
                </div>
            </div>
            
            <!-- Event Details -->
            <h6 class="border-bottom pb-2 mb-3 mt-4">Event Details</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Event Type *</label>
                    <select class="form-select" id="editEventType" required>
                        <option value="Wedding" ${booking.EventType === 'Wedding' ? 'selected' : ''}>Wedding</option>
                        <option value="Birthday" ${booking.EventType === 'Birthday' ? 'selected' : ''}>Birthday</option>
                        <option value="Corporate" ${booking.EventType === 'Corporate' ? 'selected' : ''}>Corporate Event</option>
                        <option value="Debut" ${booking.EventType === 'Debut' ? 'selected' : ''}>Debut</option>
                        <option value="Anniversary" ${booking.EventType === 'Anniversary' ? 'selected' : ''}>Anniversary</option>
                        <option value="Other" ${booking.EventType === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Event Date *</label>
                    <input type="date" class="form-control" id="editEventDate" value="${booking.EventDate}" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Event Location</label>
                    <input type="text" class="form-control" id="editEventLocation" value="${booking.EventLocation || ''}" placeholder="Enter venue or address">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Number of Guests *</label>
                    <input type="number" class="form-control" id="editNumberOfGuests" value="${booking.NumberOfGuests}" min="1" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Special Requests</label>
                <textarea class="form-control" id="editSpecialRequests" rows="3">${booking.SpecialRequests || ''}</textarea>
            </div>
            
            <!-- Status -->
            <h6 class="border-bottom pb-2 mb-3 mt-4">Booking Status</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select class="form-select" id="editStatus" required>
                        <option value="Pending" ${booking.Status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="Confirmed" ${booking.Status === 'Confirmed' ? 'selected' : ''}>Confirmed</option>
                        <option value="Completed" ${booking.Status === 'Completed' ? 'selected' : ''}>Completed</option>
                        <option value="Cancelled" ${booking.Status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="editTotalAmount" value="${booking.TotalAmount || 0}" step="0.01" readonly>
                    </div>
                    <small class="text-muted">Amount from quotation (read-only)</small>
                </div>
            </div>
        </form>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-dark" onclick="updateBooking()">
                <i class="bi bi-save me-1"></i>Save Changes
            </button>
        </div>
    `;
}

// Update booking
async function updateBooking() {
    const form = document.getElementById('editBookingForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const bookingID = document.getElementById('editBookingID').value;
    const updateData = {
        booking_id: bookingID,
        event_type: document.getElementById('editEventType').value,
        event_date: document.getElementById('editEventDate').value,
        event_location: document.getElementById('editEventLocation').value,
        number_of_guests: parseInt(document.getElementById('editNumberOfGuests').value),
        special_requests: document.getElementById('editSpecialRequests').value,
        status: document.getElementById('editStatus').value
    };

    try {
        const response = await fetch(API_BASE + 'bookings/index.php', {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updateData)
        });

        const result = await response.json();

        if (result.success) {
            alert('Booking updated successfully!');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewEditBookingModal'));
            if (modal) {
                modal.hide();
            }
            
            // Reload bookings data
            loadBookingsData();
            if (typeof loadDashboardData === 'function') {
                loadDashboardData();
            }
        } else {
            alert('Error updating booking: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating booking:', error);
        alert('Failed to update booking. Please try again.');
    }
}

// ===== VIEW QUOTATION DETAILS =====
async function viewQuotation(quotationID) {
    const modal = new bootstrap.Modal(document.getElementById('viewEditBookingModal'));
    document.getElementById('viewEditModalTitle').textContent = 'Quotation Details';
    modal.show();

    const content = document.getElementById('viewEditBookingContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading quotation details...</p>
        </div>
    `;

    try {
        const response = await fetch(API_BASE + 'quotations/index.php?id=' + quotationID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.quotations && data.data.quotations.length > 0) {
            const quotation = data.data.quotations[0];
            displayQuotationDetails(quotation);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Quotation not found</div>';
        }
    } catch (error) {
        console.error('Error loading quotation details:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load quotation details</div>';
    }
}

// Display quotation details
function displayQuotationDetails(quotation) {
    const content = document.getElementById('viewEditBookingContent');

    const statusBadgeClass =
        quotation.Status === 'Approved' ? 'success' :
        quotation.Status === 'Pending' ? 'warning' :
        'secondary';

    content.innerHTML = `
        <div class="quotation-details">
            <!-- Quotation Info Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h4 class="mb-1">Quotation #Q${quotation.QuotationID}</h4>
                    <p class="text-muted mb-0">Booking ID: #${quotation.BookingID || 'N/A'}</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-${statusBadgeClass} fs-6">${quotation.Status}</span>
                </div>
            </div>
            
            <!-- Client Information -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-fill me-2"></i>Client Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> ${quotation.ClientName || 'N/A'}</p>
                            <p class="mb-2"><strong>Email:</strong> ${quotation.ClientEmail || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Contact:</strong> ${quotation.ContactNumber || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Details -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Event Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Event Type:</strong> ${quotation.EventType || 'N/A'}</p>
                            <p class="mb-2"><strong>Event Date:</strong> ${quotation.EventDate || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Location:</strong> ${quotation.EventLocation || 'TBD'}</p>
                            <p class="mb-2"><strong>Number of Guests:</strong> ${quotation.NumberOfGuests || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Special Requests / Details -->
            ${quotation.SpecialRequest ? `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Special Requests / Details</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">${quotation.SpecialRequest}</p>
                </div>
            </div>
            ` : ''}
            
            <!-- Pricing Information -->
            <div class="card border-dark">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Estimated Price</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Total Estimate:</h5>
                        <h3 class="mb-0 text-primary">₱${parseFloat(quotation.EstimatedPrice || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer mt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            ${quotation.Status === 'Pending' ? `
                <button type="button" class="btn btn-success" onclick="approveQuotation(${quotation.QuotationID}); bootstrap.Modal.getInstance(document.getElementById('viewEditBookingModal')).hide();">
                    <i class="bi bi-check-circle me-1"></i>Approve
                </button>
                <button type="button" class="btn btn-danger" onclick="rejectQuotation(${quotation.QuotationID}); bootstrap.Modal.getInstance(document.getElementById('viewEditBookingModal')).hide();">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            ` : ''}
        </div>
    `;
}


async function approveQuotation(id) {
    if (confirm('Approve this quotation?')) {
        try {
            const response = await fetch(API_BASE + 'quotations/index.php', {
                method: 'PUT',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    quotation_id: id,
                    status: 'Approved'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Quotation approved successfully!');
                loadQuotationsData();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error approving quotation:', error);
            alert('Failed to approve quotation');
        }
    }
}

// ===== EDIT MENU =====
async function editMenu(menuID) {
    try {
        const response = await fetch(API_BASE + 'menus/index.php?id=' + menuID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.menus && data.data.menus.length > 0) {
            const menu = data.data.menus[0];
            showEditMenuModal(menu);
        } else {
            alert('Menu item not found');
        }
    } catch (error) {
        console.error('Error loading menu:', error);
        alert('Failed to load menu item');
    }
}

function showEditMenuModal(menu) {
    // Populate the existing addMenuModal with menu data
    document.querySelector('#addMenuModal .modal-title').textContent = 'Edit Menu Item';
    document.querySelector('#addMenuForm input[name="dish_name"]').value = menu.DishName;
    document.querySelector('#addMenuForm textarea[name="description"]').value = menu.Description || '';
    document.querySelector('#addMenuForm input[name="menu_price"]').value = menu.MenuPrice;
    // Add after the menu_price line:
document.querySelector('#addMenuForm input[name="image_url"]').value = menu.ImageURL || '';
document.querySelector('#addMenuForm select[name="category"]').value = menu.Category || 'other';
    
    // Store menu ID for update
    document.getElementById('addMenuForm').dataset.menuId = menu.MenuID;
    document.getElementById('addMenuForm').dataset.editMode = 'true';
    
    // Change button text
    const submitBtn = document.querySelector('#addMenuModal .modal-footer .btn-dark');
    submitBtn.textContent = 'Update Menu';
    submitBtn.onclick = function() { updateMenu(); };
    
    // Show modal
    new bootstrap.Modal(document.getElementById('addMenuModal')).show();
}

async function updateMenu() {
    const form = document.getElementById('addMenuForm');
    const formData = new FormData(form);
    const menuID = form.dataset.menuId;

    // Replace the data object with:
const data = {
    menu_id: parseInt(menuID),
    dish_name: formData.get('dish_name'),
    description: formData.get('description'),
    menu_price: parseFloat(formData.get('menu_price')),
    image_url: formData.get('image_url'),
    category: formData.get('category')
};

    try {
        const response = await fetch(API_BASE + 'menus/index.php', {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Menu item updated successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addMenuModal')).hide();
            resetMenuForm();
            loadMenusData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating menu:', error);
        alert('Failed to update menu item');
    }
}

// Reset menu form to add mode
function resetMenuForm() {
    const form = document.getElementById('addMenuForm');
    form.reset();
    delete form.dataset.menuId;
    delete form.dataset.editMode;
    
    document.querySelector('#addMenuModal .modal-title').textContent = 'Add Menu Item';
    const submitBtn = document.querySelector('#addMenuModal .modal-footer .btn-dark');
    submitBtn.textContent = 'Add Menu';
    submitBtn.onclick = function() { submitMenu(); };
}

// Make sure to reset form when modal closes
document.addEventListener('DOMContentLoaded', function() {
    const menuModal = document.getElementById('addMenuModal');
    if (menuModal) {
        menuModal.addEventListener('hidden.bs.modal', function() {
            if (document.getElementById('addMenuForm').dataset.editMode) {
                resetMenuForm();
            }
        });
    }
});

async function deleteMenu(id) {
    if (confirm('Are you sure you want to delete this menu item?')) {
        try {
            const response = await fetch(API_BASE + 'menus/index.php', {
                method: 'DELETE',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ menu_id: id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Menu deleted successfully!');
                loadMenusData();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting menu:', error);
            alert('Failed to delete menu');
        }
    }
}

// ===== VIEW CLIENT =====
async function viewClient(clientID) {
    const modal = new bootstrap.Modal(document.getElementById('viewEditBookingModal'));
    document.getElementById('viewEditModalTitle').textContent = 'Client Details';
    modal.show();

    const content = document.getElementById('viewEditBookingContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading client details...</p>
        </div>
    `;

    try {
        const response = await fetch(API_BASE + 'clients/index.php?id=' + clientID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.clients && data.data.clients.length > 0) {
            const client = data.data.clients[0];
            displayClientDetails(client);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Client not found</div>';
        }
    } catch (error) {
        console.error('Error loading client details:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load client details</div>';
    }
}

function displayClientDetails(client) {
    const content = document.getElementById('viewEditBookingContent');

    content.innerHTML = `
        <div class="client-details">
            <!-- Client Info Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h4 class="mb-1">${client.Name}</h4>
                    <p class="text-muted mb-0">Client ID: #${client.ClientID}</p>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Email:</strong> ${client.Email}</p>
                            <p class="mb-2"><strong>Phone:</strong> ${client.ContactNumber}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Address:</strong> ${client.Address || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Statistics -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Booking Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3 class="text-primary">${client.TotalBookings || 0}</h3>
                            <p class="text-muted mb-0">Total Bookings</p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="text-success">₱${parseFloat(client.TotalSpent || 0).toLocaleString()}</h3>
                            <p class="text-muted mb-0">Total Spent</p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="text-info">${client.LastEventDate || 'N/A'}</h3>
                            <p class="text-muted mb-0">Last Event</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Account Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Email Verified:</strong> ${client.IsEmailVerified ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>'}</p>
                    <p class="mb-2"><strong>Member Since:</strong> ${new Date(client.CreatedAt).toLocaleDateString()}</p>
                    <p class="mb-0"><strong>Last Updated:</strong> ${new Date(client.UpdatedAt).toLocaleDateString()}</p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer mt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-dark" onclick="editClient(${client.ClientID})">
                <i class="bi bi-pencil me-1"></i>Edit Client
            </button>
        </div>
    `;
}

// ===== EDIT CLIENT =====
async function editClient(clientID) {
    try {
        const response = await fetch(API_BASE + 'clients/index.php?id=' + clientID, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data.clients && data.data.clients.length > 0) {
            const client = data.data.clients[0];
            showEditClientModal(client);
        } else {
            alert('Client not found');
        }
    } catch (error) {
        console.error('Error loading client:', error);
        alert('Failed to load client');
    }
}

function showEditClientModal(client) {
    // Populate the existing addClientModal with client data
    document.querySelector('#addClientModal .modal-title').textContent = 'Edit Client';
    document.querySelector('#addClientForm input[name="name"]').value = client.Name;
    document.querySelector('#addClientForm input[name="email"]').value = client.Email;
    document.querySelector('#addClientForm input[name="contact_number"]').value = client.ContactNumber;
    document.querySelector('#addClientForm textarea[name="address"]').value = client.Address || '';
    
    // Store client ID for update
    document.getElementById('addClientForm').dataset.clientId = client.ClientID;
    document.getElementById('addClientForm').dataset.editMode = 'true';
    
    // Change button text
    const submitBtn = document.querySelector('#addClientModal .modal-footer .btn-dark');
    submitBtn.textContent = 'Update Client';
    submitBtn.onclick = function() { updateClient(); };
    
    // Show modal
    new bootstrap.Modal(document.getElementById('addClientModal')).show();
}

async function updateClient() {
    const form = document.getElementById('addClientForm');
    const formData = new FormData(form);
    const clientID = form.dataset.clientId;

    const data = {
        client_id: parseInt(clientID),
        name: formData.get('name'),
        email: formData.get('email'),
        contact_number: formData.get('contact_number'),
        address: formData.get('address')
    };

    try {
        const response = await fetch(API_BASE + 'clients/index.php', {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Client updated successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addClientModal')).hide();
            resetClientForm();
            loadClientsData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating client:', error);
        alert('Failed to update client');
    }
}

// Reset client form to add mode
function resetClientForm() {
    const form = document.getElementById('addClientForm');
    form.reset();
    delete form.dataset.clientId;
    delete form.dataset.editMode;
    
    document.querySelector('#addClientModal .modal-title').textContent = 'Add New Client';
    const submitBtn = document.querySelector('#addClientModal .modal-footer .btn-dark');
    submitBtn.textContent = 'Add Client';
    submitBtn.onclick = function() { submitClient(); };
}

// Make sure to reset form when modal closes
document.addEventListener('DOMContentLoaded', function() {
    const clientModal = document.getElementById('addClientModal');
    if (clientModal) {
        clientModal.addEventListener('hidden.bs.modal', function() {
            if (document.getElementById('addClientForm').dataset.editMode) {
                resetClientForm();
            }
        });
    }
});

// ===== NAVIGATION HANDLER =====
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    
    loadPage('dashboard');
    
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            loadPage(page);
        });
    });
    
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
});

// Set minimum date for date inputs (prevent past dates)
function setMinimumDates() {
    // Get today's date in YYYY-MM-DD format
    const today = new Date().toISOString().split('T')[0];
    
    // Set minimum date for all date inputs in modals
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.setAttribute('min', today);
    });
}

// Call setMinimumDates when modals are opened
document.addEventListener('DOMContentLoaded', function() {
    // For Add Quotation Modal
    const quotationModal = document.getElementById('addQuotationModal');
    if (quotationModal) {
        quotationModal.addEventListener('show.bs.modal', function() {
            setMinimumDates();
        });
    }
    
    // For Add Booking Modal (if it exists)
    const bookingModal = document.getElementById('addBookingModal');
    if (bookingModal) {
        bookingModal.addEventListener('show.bs.modal', function() {
            setMinimumDates();
        });
    }
    
    // Set minimum dates on initial page load
    setMinimumDates();
});

// Test API connection for services
async function testAPIConnection() {
    try {
        console.log('Testing Services API...');
        console.log('Full URL:', API_BASE + 'services/index.php');
        
        const response = await fetch(API_BASE + 'services/index.php', { 
            method: 'GET',
            credentials: 'include' 
        });
        
        console.log('Response status:', response.status);
        console.log('Response OK:', response.ok);
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
        }
    } catch (error) {
        console.error('API Test Error:', error);
    }
}
// ============================================================
// BOOKING MANAGEMENT FUNCTIONS - START
// ============================================================

/**
 * Cancel a booking with confirmation dialog
 */
async function cancelBooking(bookingId, clientName) {
    const reason = prompt(
        `Cancel booking #${bookingId} for ${clientName}?\n\nPlease provide a cancellation reason:`,
        'Client request'
    );
    
    if (reason === null) return;
    if (reason.trim() === '') {
        alert('Please provide a cancellation reason.');
        return;
    }
    
    try {
        showLoadingSpinner();
        
        const response = await fetch('/admin/api/bookings/index.php?action=cancel', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getAuthToken()
            },
            body: JSON.stringify({
                booking_id: bookingId,
                cancel_reason: reason
            })
        });
        
        const result = await response.json();
        hideLoadingSpinner();
        
        if (result.success) {
            showSuccessMessage(`Booking #${bookingId} has been cancelled successfully.`);
            setTimeout(() => location.reload(), 1500);
        } else {
            showErrorMessage(result.message || 'Failed to cancel booking.');
        }
    } catch (error) {
        hideLoadingSpinner();
        console.error('Cancel booking error:', error);
        showErrorMessage('An error occurred while cancelling the booking. Please try again.');
    }
}

/**
 * Reactivate a cancelled booking
 */
async function reactivateBooking(bookingId) {
    if (!confirm(`Reactivate booking #${bookingId}?\n\nThis will change the status back to Pending.`)) {
        return;
    }
    
    try {
        showLoadingSpinner();
        
        const response = await fetch('/admin/api/bookings/index.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getAuthToken()
            },
            body: JSON.stringify({
                booking_id: bookingId,
                status: 'Pending'
            })
        });
        
        const result = await response.json();
        hideLoadingSpinner();
        
        if (result.success) {
            showSuccessMessage(`Booking #${bookingId} has been reactivated.`);
            setTimeout(() => location.reload(), 1500);
        } else {
            showErrorMessage(result.message || 'Failed to reactivate booking.');
        }
    } catch (error) {
        hideLoadingSpinner();
        console.error('Reactivate booking error:', error);
        showErrorMessage('An error occurred. Please try again.');
    }
}

/**
 * Filter bookings by status
 */
function filterBookingsByStatus(status) {
    const rows = document.querySelectorAll('.booking-row');
    
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const clickedBtn = event?.target;
    if (clickedBtn) clickedBtn.classList.add('active');
}

/**
 * Style cancelled booking rows
 */
function styleBookingRows() {
    document.querySelectorAll('.booking-row').forEach(row => {
        const status = row.getAttribute('data-status');
        
        if (status === 'Cancelled') {
            row.style.opacity = '0.6';
            row.style.backgroundColor = '#f8f9fa';
            
            const statusBadge = row.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = 'status-badge status-cancelled';
                statusBadge.innerHTML = '<i class="bi bi-x-circle me-1"></i>Cancelled';
            }
            
            const editBtn = row.querySelector('.btn-edit');
            if (editBtn) {
                editBtn.disabled = true;
                editBtn.style.opacity = '0.5';
                editBtn.title = 'Cannot edit cancelled bookings';
            }
            
            const cancelBtn = row.querySelector('.btn-cancel');
            if (cancelBtn) {
                cancelBtn.textContent = 'Reactivate';
                cancelBtn.className = 'btn btn-sm btn-success btn-reactivate';
                const bookingId = row.getAttribute('data-booking-id');
                cancelBtn.onclick = () => reactivateBooking(bookingId);
            }
        }
    });
}

/**
 * Helper Functions
 */
function showLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'block';
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.style.display = 'none';
}

function showSuccessMessage(message) {
    showToast('success', message);
}

function showErrorMessage(message) {
    showToast('error', message);
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} toast-notification`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
    `;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        padding: 15px 20px;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getAuthToken() {
    return sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token') || '';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    styleBookingRows();
    console.log('Booking management functions loaded');
});

// ============================================================
// BOOKING MANAGEMENT FUNCTIONS - END
// ============================================================