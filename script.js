let hostels = []; // Holds data fetched dynamically from PostgreSQL via PHP
let mapInstance = null;

document.addEventListener("DOMContentLoaded", () => {
    fetchHostelsFromDatabase();
    setupNavigation();
    setupFilters();
    setupAuth();
    setupRegistration();
    setupLandlordForm();
});

// 1. FETCH HOSTELS FROM PHP BACKEND
function fetchHostelsFromDatabase() {
    fetch('api.php')
        .then(response => response.json())
        .then(data => {
            hostels = data;
            renderHostelCards(hostels);
        })
        .catch(error => console.error("Error fetching data from PostgreSQL:", error));
}

function loadLandlordHostels() {
    const landlordId = localStorage.getItem("landlord_id");
    if (!landlordId) return;

    fetch(`api.php?landlord_id=${encodeURIComponent(landlordId)}`)
        .then(response => response.json())
        .then(landlordHostels => {
            if (!Array.isArray(landlordHostels)) {
                throw new Error(landlordHostels.error || "Invalid hostel data received.");
            }

            renderHostelCards(landlordHostels, true);
        })
        .catch(error => console.error("Error loading landlord hostels:", error));
}

// 2. NAVIGATION LOGIC
function setupNavigation() {
    const btnHome = document.getElementById("nav-home");
    const btnMap = document.getElementById("nav-map");
    const btnLandlord = document.getElementById("nav-landlord");

    const viewHome = document.getElementById("view-home");
    const viewMap = document.getElementById("view-map");
    const viewLandlord = document.getElementById("view-landlord");

    function switchTab(activeBtn, activeView) {
        [btnHome, btnMap, btnLandlord].forEach(b => b.classList.remove("active-tab"));
        [viewHome, viewMap, viewLandlord].forEach(v => v.style.display = "none");

        activeBtn.classList.add("active-tab");
        activeView.style.display = "block";

        if (activeView === viewMap) {
            initMap();
        }
    }

    btnHome.addEventListener("click", () => switchTab(btnHome, viewHome));
    btnMap.addEventListener("click", () => switchTab(btnMap, viewMap));
    btnLandlord.addEventListener("click", () => switchTab(btnLandlord, viewLandlord));
}

// 3. RENDER CARDS TO DOM
function renderHostelCards(list, isDashboard = false) {
    const gridId = isDashboard ? "landlord-listings-grid" : "hostel-grid";
    const grid = document.getElementById(gridId);
    grid.innerHTML = "";

    if (!list || list.length === 0) {
        grid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 2rem;">No hostel listings found in database.</p>`;
        return;
    }

    list.forEach(hostel => {
        const card = document.createElement("div");
        card.className = "hostel-card";

        card.innerHTML = `
            <div class="hostel-image-wrapper">
                <span class="badge">HOT CAKE 🔥</span>
                <img src="${hostel.image_url}" alt="${hostel.title}" onerror="this.src='https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=600&q=80'">
            </div>
            <div class="hostel-details">
                <h3 class="hostel-title">${hostel.title}</h3>
                <div class="price">GH¢${Number(hostel.price).toLocaleString()}</div>
                <div class="meta-info"><strong>Room:</strong> ${hostel.room_type}</div>
                <div class="meta-info">🚶‍♂️ ${hostel.distance}</div>
                <div class="meta-info">📍 ${hostel.location}</div>
                
                ${isDashboard 
                    ? `<button class="btn-danger" onclick="deleteHostel(${hostel.id})">Delete from Database</button>` 
                    : `<a href="tel:${hostel.phone}" class="btn-call">Call Contact: ${hostel.phone}</a>`
                }
            </div>
        `;
        grid.appendChild(card);
    });
}

// 4. SEARCH & FILTERS
function setupFilters() {
    const searchInput = document.getElementById("search-input");
    const roomTypeSelect = document.getElementById("filter-room-type");

    function filterData() {
        const query = searchInput.value.toLowerCase();
        const selectedType = roomTypeSelect.value;

        const filtered = hostels.filter(hostel => {
            const matchesQuery = hostel.title.toLowerCase().includes(query) || 
                                 hostel.location.toLowerCase().includes(query);
            const matchesType = selectedType === "" || hostel.room_type === selectedType;
            return matchesQuery && matchesType;
        });

        renderHostelCards(filtered);
    }

    searchInput.addEventListener("input", filterData);
    roomTypeSelect.addEventListener("change", filterData);
}

// 5. LANDLORD LOGIN & AUTH
function setupAuth() {
    const loginForm = document.getElementById("login-form");
    const loginContainer = document.getElementById("login-container");
    const dashboardContainer = document.getElementById("dashboard-container");
    const loginError = document.getElementById("login-error");
    const btnLogout = document.getElementById("btn-logout");

    loginForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const username = document.getElementById("login-user").value.trim();
        const password = document.getElementById("login-pass").value.trim();

        fetch("login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username: username, password: password })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    loginError.style.color = "green";
                    loginError.innerText = data.message;
                    localStorage.setItem("landlord_id", data.landlord_id);
                    localStorage.setItem("username", data.username);
                    loadLandlordHostels();

                    setTimeout(() => {
                        loginContainer.style.display = "none";
                        dashboardContainer.style.display = "block";
                    }, 800);
                } else {
                    loginError.style.color = "red";
                    loginError.innerText = data.message;
                }
            })
            .catch(error => {
                console.error("Login error:", error);
                loginError.style.color = "red";
                loginError.innerText = "Connection error. Please try again.";
            });
    });

    btnLogout.addEventListener("click", () => {
        localStorage.removeItem("landlord_id");
        localStorage.removeItem("username");

        fetch("logout.php", { method: "POST" })
            .then(response => response.json())
            .then(data => console.log(data.message))
            .catch(error => console.error("Logout error:", error));

        document.getElementById("login-user").value = "";
        document.getElementById("login-pass").value = "";
        loginError.innerText = "";
        dashboardContainer.style.display = "none";
        loginContainer.style.display = "block";
    });
}

// 6. LANDLORD REGISTRATION
function setupRegistration() {
    const registerForm = document.getElementById("registerForm");

    registerForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const username = document.getElementById("username").value;
        const password = document.getElementById("password").value;
        const phone = document.getElementById("phone").value;
        const msgDiv = document.getElementById("responseMessage");

        fetch("register.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                username: username,
                password: password,
                phone_number: phone
            })
        })
            .then(response => response.json())
            .then(data => {
                console.log("Server response:", data);
                msgDiv.style.color = data.status === "success" ? "green" : "red";
                msgDiv.innerText = data.message;
            })
            .catch(error => {
                console.error("Error:", error);
                msgDiv.style.color = "red";
                msgDiv.innerText = "Unable to complete registration. Please try again.";
            });
    });
}

// 6. ADD LISTING TO PHP/POSTGRESQL
function setupLandlordForm() {
    const addForm = document.getElementById("add-hostel-form");

    addForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const landlordId = localStorage.getItem("landlord_id");
        const hostelData = {
            landlord_id: landlordId,
            title: document.getElementById("new-title").value,
            room_type: document.getElementById("new-room-type").value,
            price: document.getElementById("new-price").value,
            distance: document.getElementById("new-distance").value,
            location: document.getElementById("new-location").value,
            phone: document.getElementById("new-phone").value,
            image_url: document.getElementById("new-image").value || "https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=600&q=80",
            lat: 6.685,
            lng: -1.623
        };

        fetch("post-hostel.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(hostelData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert(data.message);
                    addForm.reset();
                    fetchHostelsFromDatabase();
                    loadLandlordHostels();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error("Posting error:", error);
                alert("Unable to post the hostel. Please try again.");
            });
    });
}

// Global delete function
window.deleteHostel = function(id) {
    if (confirm("Are you sure you want to delete this listing from the database?")) {
        fetch(`api.php?id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    fetchHostelsFromDatabase();
                    setTimeout(() => renderHostelCards(hostels, true), 300);
                }
            });
    }
};

// 7. LEAFLET MAP INTEGRATION
function initMap() {
    if (mapInstance) {
        mapInstance.invalidateSize();
        return;
    }

    mapInstance = L.map('map').setView([6.6920, -1.6160], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    hostels.forEach(hostel => {
        if(hostel.lat && hostel.lng) {
            const marker = L.marker([Number(hostel.lat), Number(hostel.lng)]).addTo(mapInstance);
            marker.bindPopup(`
                <div style="width: 180px;">
                    <img src="${hostel.image_url}" style="width:100%; height:100px; object-fit:cover; border-radius:4px; margin-bottom:5px;">
                    <h4 style="font-size:0.95rem; margin-bottom:3px;">${hostel.title}</h4>
                    <p style="color: #16a34a; font-weight:bold; margin-bottom:3px;">GH¢${Number(hostel.price).toLocaleString()}</p>
                    <p style="font-size:0.8rem; color:#64748b;">${hostel.distance}</p>
                </div>
            `);
        }
    });
}