// admin-component-loader.js - Dynamic Layout Loader for Admin Side Dashboard

// 1. Strict Session & Route Guard (Runs immediately to prevent flashing/rendering of unauthorized pages)
(function() {
  const staffId = localStorage.getItem("logged_account_id");
  const staffName = localStorage.getItem("logged_staff_name");
  const staffRole = localStorage.getItem("logged_staff_role");
  const isLoginPage = window.location.pathname.includes("login.html");

  if (!isLoginPage) {
    if (!staffId || !staffName) {
      window.location.href = "/adminSide/StaffLogin/login.html";
      return;
    }

    // Role-based page access control
    const currentPath = window.location.pathname;
    if (staffRole === "Waiter") {
      const isAllowedPage = currentPath.includes("pos-panel.html") || 
                            currentPath.includes("orderItem.html") || 
                            currentPath.includes("receipt.html");
      if (!isAllowedPage) {
        window.location.href = "/adminSide/panel/pos-panel.html";
        return;
      }
    } else if (staffRole === "Chef") {
      const isAllowedPage = currentPath.includes("kitchen-panel.html");
      if (!isAllowedPage) {
        window.location.href = "/adminSide/panel/kitchen-panel.html";
        return;
      }
    }
  }

  // Inject custom sidebar stylesheet to support both light/dark themes
  const style = document.createElement("style");
  style.innerHTML = `
      /* Dark Sidebar theme styles */
      .sb-sidenav-dark .staff-profile-card {
          background: rgba(255, 255, 255, 0.04) !important;
          border: 1px solid rgba(255, 255, 255, 0.06) !important;
      }
      .sb-sidenav-dark .staff-profile-id { color: #ffffff !important; }
      .sb-sidenav-dark .staff-profile-name { color: #cbd5e1 !important; }
      .sb-sidenav-dark .recent-order-card {
          background: rgba(255, 255, 255, 0.04) !important;
          border: 1px solid rgba(255, 255, 255, 0.06) !important;
          color: #ccc !important;
      }
      .sb-sidenav-dark .recent-order-title { color: #ffffff !important; }

      /* Light Sidebar theme styles (also matches if .sb-sidenav-dark is missing) */
      .sb-sidenav-light .staff-profile-card,
      #sidenavAccordion:not(.sb-sidenav-dark) .staff-profile-card {
          background: rgba(0, 0, 0, 0.03) !important;
          border: 1px solid rgba(0, 0, 0, 0.06) !important;
      }
      .sb-sidenav-light .staff-profile-id,
      #sidenavAccordion:not(.sb-sidenav-dark) .staff-profile-id { 
          color: #0F172A !important; 
      }
      .sb-sidenav-light .staff-profile-name,
      #sidenavAccordion:not(.sb-sidenav-dark) .staff-profile-name { 
          color: #475569 !important; 
      }
      .sb-sidenav-light .recent-order-card,
      #sidenavAccordion:not(.sb-sidenav-dark) .recent-order-card {
          background: rgba(0, 0, 0, 0.02) !important;
          border: 1px solid rgba(0, 0, 0, 0.06) !important;
          color: #475569 !important;
      }
      .sb-sidenav-light .recent-order-title,
      #sidenavAccordion:not(.sb-sidenav-dark) .recent-order-title { 
          color: #0F172A !important; 
      }
  `;
  document.head.appendChild(style);
})();

document.addEventListener("DOMContentLoaded", function () {
  const staffId = localStorage.getItem("logged_account_id");
  const staffName = localStorage.getItem("logged_staff_name");
  const staffRole = localStorage.getItem("logged_staff_role");
  const isLoginPage = window.location.pathname.includes("login.html");

  if (isLoginPage) return;

  // 2. Fetch and Inject Dashboard Header Navbar/Sidebar
  const headerContainer = document.getElementById("admin-header");
  if (headerContainer) {
    fetch('/adminSide/inc/dashHeader.html?v=' + Date.now())
      .then(response => {
        if (!response.ok) throw new Error("Dashboard header not found");
        return response.text();
      })
      .then(html => {
        headerContainer.innerHTML = html;
        
        if (staffRole === "Waiter" || staffRole === "Chef") {
          // Adjust Brand logo redirection link based on role
          const brandLink = headerContainer.querySelector(".navbar-brand");
          if (brandLink) {
            brandLink.href = staffRole === "Waiter" ? "/adminSide/panel/pos-panel.html" : "/adminSide/panel/kitchen-panel.html";
          }
          
          // Replace notification button with a red log out button
          const notifBtn = document.getElementById("navbar-notification-btn");
          if (notifBtn) {
            notifBtn.id = "navbar-logout-btn";
            notifBtn.href = "#";
            notifBtn.title = "Log Out";
            notifBtn.innerHTML = `<i class="fas fa-sign-out-alt" style="color: #EF4444; font-size: 1.25rem;"></i>`;
            notifBtn.addEventListener("click", function (e) {
              e.preventDefault();
              if (confirm("Are you sure you want to log out?")) {
                localStorage.removeItem("logged_account_id");
                localStorage.removeItem("logged_staff_name");
                localStorage.removeItem("logged_staff_role");
                localStorage.removeItem("admin_theme");
                window.location.href = "/adminSide/StaffLogin/login.html";
              }
            });
          }

          // Customize sidebar content for Waiter/Chef
          const navElement = headerContainer.querySelector("#sidenavAccordion .sb-sidenav-menu .nav");
          if (navElement) {
            if (staffRole === "Waiter") {
              navElement.innerHTML = `
                <div class="sb-sidenav-menu-heading" style="color: #94A3B8 !important; padding-top: 1rem;">Staff Profile</div>
                <div class="px-3 py-2 staff-profile-card" style="border-radius: 12px; margin: 0 12px 15px 12px;">
                    <div class="staff-profile-id" style="font-size: 0.9em; font-weight: bold;">ID: ${staffId}</div>
                    <div class="staff-profile-name" style="font-size: 0.95em; font-weight: 500; margin-top: 2px;">Name: ${staffName}</div>
                    <div style="font-size: 0.85em; color: #F59E0B; font-weight: 700; margin-top: 4px; text-transform: uppercase; display: flex; align-items: center; gap: 5px;">
                      <span style="display: inline-block; width: 6px; height: 6px; background-color: #F59E0B; border-radius: 50%;"></span>
                      Waiter
                    </div>
                </div>
                <div class="sb-sidenav-menu-heading" style="color: #94A3B8 !important;">My Recent Orders</div>
                <div id="waiter-recent-orders-container" class="px-3" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-muted small">Loading orders...</p>
                </div>
                <div style="margin-top: auto; padding-bottom: 20px;">
                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 12px;">
                    <a class="nav-link" id="sidebar-logout-btn" href="#" style="color: #EF4444 !important; font-weight: bold; border-radius: 12px; margin: 2px 12px; padding: 10px 16px; display: flex; align-items: center; gap: 10px; transition: background-color 0.2s;">
                        <i class="fas fa-sign-out-alt" style="color: #EF4444;"></i>
                        Log Out
                    </a>
                </div>
              `;
              
              // Bind logout action
              document.getElementById("sidebar-logout-btn").addEventListener("click", function(e) {
                e.preventDefault();
                if (confirm("Are you sure you want to log out?")) {
                  localStorage.clear();
                  window.location.href = "/adminSide/StaffLogin/login.html";
                }
              });

              loadWaiterRecentOrders(staffId);
              initializeAdminSidebar();
            } else if (staffRole === "Chef") {
              navElement.innerHTML = `
                <div class="sb-sidenav-menu-heading" style="color: #94A3B8 !important; padding-top: 1rem;">Staff Profile</div>
                <div class="px-3 py-2 staff-profile-card" style="border-radius: 12px; margin: 0 12px 15px 12px;">
                    <div class="staff-profile-id" style="font-size: 0.9em; font-weight: bold;">ID: ${staffId}</div>
                    <div class="staff-profile-name" style="font-size: 0.95em; font-weight: 500; margin-top: 2px;">Name: ${staffName}</div>
                    <div style="font-size: 0.85em; color: #10B981; font-weight: 700; margin-top: 4px; text-transform: uppercase; display: flex; align-items: center; gap: 5px;">
                      <span style="display: inline-block; width: 6px; height: 6px; background-color: #10B981; border-radius: 50%;"></span>
                      Chef
                    </div>
                </div>
                <div class="sb-sidenav-menu-heading" style="color: #94A3B8 !important;">My Completed Orders</div>
                <div id="chef-recent-orders-container" class="px-3" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-muted small">No completed orders yet.</p>
                </div>
                <div style="margin-top: auto; padding-bottom: 20px;">
                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 12px;">
                    <a class="nav-link" id="sidebar-logout-btn" href="#" style="color: #EF4444 !important; font-weight: bold; border-radius: 12px; margin: 2px 12px; padding: 10px 16px; display: flex; align-items: center; gap: 10px; transition: background-color 0.2s;">
                        <i class="fas fa-sign-out-alt" style="color: #EF4444;"></i>
                        Log Out
                    </a>
                </div>
              `;

              // Bind logout action
              document.getElementById("sidebar-logout-btn").addEventListener("click", function(e) {
                e.preventDefault();
                if (confirm("Are you sure you want to log out?")) {
                  localStorage.clear();
                  window.location.href = "/adminSide/StaffLogin/login.html";
                }
              });

              loadChefRecentOrders(staffId);
              initializeAdminSidebar();
            }
          }
        } else {
          initializeAdminSidebar();
          observePendingOrders();
          observeNotifications();
        }
      })
      .catch(err => console.error("Error loading dashboard header:", err));
  }

  // Bind Sidebar functionality & Session details
  function initializeAdminSidebar() {
    // Populate session details in sidebar footer
    document.getElementById("sidebar-staff-id").textContent = `ID: ${staffId}`;
    document.getElementById("sidebar-staff-name").textContent = `Name: ${staffName}`;



    // Handle Toggle Navigation (from SB Admin)
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    // Intercept clicks on unmigrated tabs to display a beautiful dynamic alert
    const unmigratedPages = [];

    document.querySelectorAll(".sb-sidenav .nav-link").forEach(link => {
        const hrefAttr = link.getAttribute("href");
        if (hrefAttr && unmigratedPages.some(page => hrefAttr.includes(page))) {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                const moduleName = this.textContent.trim();
                
                // Show elegant alert at the top of the content page
                const alertContainer = document.getElementById("message-container") || document.querySelector("main .container-fluid");
                if (alertContainer) {
                    const oldAlert = document.getElementById("unmigrated-alert");
                    if (oldAlert) oldAlert.remove();

                    const alertDiv = document.createElement("div");
                    alertDiv.id = "unmigrated-alert";
                    alertDiv.className = "alert alert-warning alert-dismissible fade show mt-3";
                    alertDiv.setAttribute("role", "alert");
                    alertDiv.innerHTML = `
                        <i class="fa fa-info-circle mr-2"></i> 
                        The <strong>${moduleName} Module</strong> is currently locked under your free cardless database plan. 
                        Please use the active <strong>Customer Orders</strong> and <strong>Kitchen</strong> panels to run your live operations!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    `;
                    
                    alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert(`The ${moduleName} module is currently locked under your free plan. Please use Customer Orders and Kitchen to operate the live POS system.`);
                }
            });
        }
    });

    // Bind Logout Button
    const logoutBtn = document.getElementById("admin-logout-btn");
    if (logoutBtn) {
      logoutBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (confirm("Are you sure you want to log out of the Staff Panel?")) {
          localStorage.removeItem("logged_account_id");
          localStorage.removeItem("logged_staff_name");
          localStorage.removeItem("logged_staff_role");
          localStorage.removeItem("admin_theme");
          window.location.href = "/adminSide/StaffLogin/login.html";
        }
      });
    }

    // Enable smooth transitions post render paint
    setTimeout(() => {
        document.documentElement.classList.remove('preload');
    }, 100);
  }

  // Real-time Pending Orders Badge Counter in sidebar
  function observePendingOrders() {
    if (typeof firebase === 'undefined' || !window.db) {
      setTimeout(observePendingOrders, 500);
      return;
    }

    window.db.collection("customer_orders")
      .where("status", "==", "Pending")
      .onSnapshot(querySnapshot => {
        const badge = document.getElementById("sidebar-order-badge");
        if (!badge) return;

        // Group by request_group_id to find unique order groups
        const uniqueGroups = new Set();
        querySnapshot.forEach(doc => {
          uniqueGroups.add(doc.data().request_group_id);
        });

        const pendingCount = uniqueGroups.size;
        if (pendingCount > 0) {
          badge.textContent = pendingCount;
          badge.style.display = "inline-block";
        } else {
          badge.style.display = "none";
        }
      }, err => {
        console.error("Sidebar pending orders listener failed:", err);
      });
  }

  // Real-time notifications observer
  function observeNotifications() {
    if (typeof firebase === 'undefined' || !window.db) {
      setTimeout(observeNotifications, 500);
      return;
    }

    // 1. Initialize unread badge on load
    const badge = document.getElementById("navbar-notification-badge");
    if (badge && sessionStorage.getItem("unread_notifications") === "true") {
      badge.style.display = "block";
    }

    let prevTables = new Map();
    let isInitialTables = true;
    window.db.collection("tables").onSnapshot(snapshot => {
      snapshot.docChanges().forEach(change => {
        const data = change.doc.data();
        const docId = change.doc.id;
        if (change.type === "added") {
          prevTables.set(docId, data);
          if (!isInitialTables) {
            triggerNotification(`New Table Added: Table ${data.table_id} (${data.capacity} seats).`);
          }
        } else if (change.type === "modified") {
          const before = prevTables.get(docId);
          prevTables.set(docId, data);
          if (!isInitialTables && before && before.status !== data.status) {
            let message = "";
            if (data.status === "Occupied") {
              message = `Table ${data.table_id} is now Occupied.`;
            } else if (data.status === "Available") {
              message = `Table ${data.table_id} is now Available.`;
            } else if (data.status === "Reserved") {
              message = `Table ${data.table_id} is now Reserved.`;
            } else {
              message = `Table ${data.table_id} status changed to ${data.status}.`;
            }
            triggerNotification(message);
          }
        } else if (change.type === "removed") {
          prevTables.delete(docId);
          if (!isInitialTables) {
            triggerNotification(`Table ${data.table_id} has been removed.`);
          }
        }
      });
      isInitialTables = false;
    }, err => console.error("Notification tables listener failed:", err));

    let prevReservations = new Map();
    let isInitialReservations = true;
    window.db.collection("reservations").onSnapshot(snapshot => {
      snapshot.docChanges().forEach(change => {
        const data = change.doc.data();
        const docId = change.doc.id;
        if (change.type === "added") {
          prevReservations.set(docId, data);
          if (!isInitialReservations) {
            triggerNotification(`New Reservation: Table ${data.table_id} reserved for ${data.customer_name} on ${data.reservation_date} at ${data.reservation_time}.`);
          }
        } else if (change.type === "modified") {
          const before = prevReservations.get(docId);
          prevReservations.set(docId, data);
          if (!isInitialReservations && before) {
            if (before.reservation_date !== data.reservation_date || before.reservation_time !== data.reservation_time || before.table_id !== data.table_id) {
              triggerNotification(`Reservation Modified: ${data.customer_name} rescheduled to Table ${data.table_id} on ${data.reservation_date} at ${data.reservation_time}.`);
            }
          }
        } else if (change.type === "removed") {
          const before = prevReservations.get(docId);
          prevReservations.delete(docId);
          if (!isInitialReservations) {
            const name = before ? before.customer_name : "Customer";
            triggerNotification(`Reservation Deleted: Reservation for ${name} has been cancelled.`);
          }
        }
      });
      isInitialReservations = false;
    }, err => console.error("Notification reservations listener failed:", err));

    // Upcoming 1-hour reservation notifier
    function checkUpcomingReservations() {
      const notified = new Set(JSON.parse(sessionStorage.getItem("notified_upcoming_res") || "[]"));
      const now = new Date();
      const localTodayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

      prevReservations.forEach((res, docId) => {
        if (!res.reservation_date || !res.reservation_time) return;
        if (res.reservation_date !== localTodayStr) return;

        const dateParts = res.reservation_date.split("-");
        const timeParts = res.reservation_time.split(":");
        if (dateParts.length < 3 || timeParts.length < 2) return;

        // Parse reservation datetime in local timezone
        const resDateTime = new Date(
            parseInt(dateParts[0]),
            parseInt(dateParts[1]) - 1, // Month index is 0-based
            parseInt(dateParts[2]),
            parseInt(timeParts[0]),
            parseInt(timeParts[1]),
            timeParts[2] ? parseInt(timeParts[2]) : 0
        );

        const diffMs = resDateTime.getTime() - now.getTime();

        // 1 hour is 3,600,000 ms. We notify if the reservation is in the next 1 hour (and hasn't passed)
        if (diffMs > 0 && diffMs <= 3600000) {
          if (!notified.has(docId)) {
            triggerNotification(`Upcoming Reservation: Table ${res.table_id} for ${res.customer_name} starts in less than 1 hour (at ${res.reservation_time}).`);
            notified.add(docId);
          }
        }
      });

      sessionStorage.setItem("notified_upcoming_res", JSON.stringify(Array.from(notified)));
    }
    setInterval(checkUpcomingReservations, 10000);

    let prevMenu = new Map();
    let isInitialMenu = true;
    window.db.collection("menu").onSnapshot(snapshot => {
      snapshot.docChanges().forEach(change => {
        const data = change.doc.data();
        const docId = change.doc.id;
        if (change.type === "added") {
          prevMenu.set(docId, data);
          if (!isInitialMenu) {
            triggerNotification(`New Menu Item: "${data.item_name}" added.`);
          }
        } else if (change.type === "modified") {
          const before = prevMenu.get(docId);
          prevMenu.set(docId, data);
          if (!isInitialMenu && before && before.status !== data.status) {
            triggerNotification(`Menu Item: "${data.item_name}" is now ${data.status}.`);
          }
        } else if (change.type === "removed") {
          prevMenu.delete(docId);
          if (!isInitialMenu) {
            triggerNotification(`Menu Item: "${data.item_name}" has been deleted.`);
          }
        }
      });
      isInitialMenu = false;
    }, err => console.error("Notification menu listener failed:", err));

    let prevBills = new Map();
    let isInitialBills = true;
    window.db.collection("bills").onSnapshot(snapshot => {
      snapshot.docChanges().forEach(change => {
        const data = change.doc.data();
        const docId = change.doc.id;
        const billId = data.bill_id || docId;
        if (change.type === "added") {
          prevBills.set(docId, data);
          if (!isInitialBills) {
            triggerNotification(`New Bill Session started: Bill #${billId} for Table ${data.table_id}.`);
          }
        } else if (change.type === "modified") {
          const before = prevBills.get(docId);
          prevBills.set(docId, data);
          if (!isInitialBills && before) {
            if (before.payment_time === null && data.payment_time !== null) {
              triggerNotification(`Bill #${billId} (Table ${data.table_id}) paid: Rs ${parseFloat(data.total_amount || 0).toFixed(2)}.`);
            }
          }
        } else if (change.type === "removed") {
          prevBills.delete(docId);
          if (!isInitialBills) {
            triggerNotification(`Bill #${billId} (Table ${data.table_id}) has been deleted/discarded.`);
          }
        }
      });
      isInitialBills = false;
    }, err => console.error("Notification bills listener failed:", err));

    let isInitialKitchen = true;
    window.db.collection("kitchen").onSnapshot(snapshot => {
      snapshot.docChanges().forEach(change => {
        if (change.type === "added" && !isInitialKitchen) {
          const data = change.doc.data();
          triggerNotification(`New Kitchen Order: Table ${data.table_no} ordered (Qty: ${data.quantity}).`);
        }
      });
      isInitialKitchen = false;
    }, err => console.error("Notification kitchen listener failed:", err));

    let isInitialOnlineOrders = true;
    window.db.collection("online_orders").onSnapshot(snapshot => {
      snapshot.docChanges().forEach(change => {
        const data = change.doc.data();
        const orderId = change.doc.id;
        if (change.type === "added" && !isInitialOnlineOrders) {
          triggerNotification(`New Online Order received! Order ID: #${orderId}.`);
        } else if (change.type === "modified" && !isInitialOnlineOrders) {
          triggerNotification(`Online Order #${orderId} status updated to: ${data.order_status}.`);
        }
      });
      isInitialOnlineOrders = false;
    }, err => console.error("Notification online orders listener failed:", err));
  }

  function triggerNotification(message) {
    // 1. Save to sessionStorage
    let notifications = JSON.parse(sessionStorage.getItem("admin_notifications") || "[]");
    notifications.unshift({
      message: message,
      time: new Date().toLocaleTimeString()
    });
    sessionStorage.setItem("admin_notifications", JSON.stringify(notifications));
    sessionStorage.setItem("unread_notifications", "true");

    // 2. Show red badge on bell button
    const badge = document.getElementById("navbar-notification-badge");
    if (badge) {
      badge.style.display = "block";
    }

    // 3. Show Toast Popup
    showToastPopup(message);
  }

  function showToastPopup(message) {
    let container = document.getElementById("toast-notification-container");
    if (!container) {
      container = document.createElement("div");
      container.id = "toast-notification-container";
      container.style = "position: fixed; top: 65px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; width: 320px; pointer-events: none;";
      document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.style = "pointer-events: auto; display: flex; align-items: start; gap: 12px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 16px; padding: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); transform: translateY(-20px); opacity: 0; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); margin-bottom: 5px;";
    
    let iconClass = "fa-solid fa-circle-info";
    let textColor = "#2563EB";

    if (message.toLowerCase().includes("occupied")) {
      iconClass = "fa-solid fa-triangle-exclamation";
      textColor = "#EF4444";
    } else if (message.toLowerCase().includes("available") || message.toLowerCase().includes("free")) {
      iconClass = "fa-solid fa-circle-check";
      textColor = "#10B981";
    } else if (message.toLowerCase().includes("reservation")) {
      iconClass = "fa-solid fa-calendar-days";
      textColor = "#F59E0B";
    } else if (message.toLowerCase().includes("menu") || message.toLowerCase().includes("item")) {
      iconClass = "fa-solid fa-utensils";
      textColor = "#8B5CF6";
    }

    toast.innerHTML = `
      <div style="background: rgba(0, 0, 0, 0.03); color: ${textColor}; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 34px; height: 34px;">
        <i class="${iconClass}"></i>
      </div>
      <div style="flex-grow: 1;">
        <div style="font-size: 0.85em; color: #64748B; font-weight: bold; margin-bottom: 2px;">Notification</div>
        <div style="font-size: 0.9em; color: #0F172A; font-weight: 500; line-height: 1.3;">${message}</div>
      </div>
      <button type="button" style="background: none; border: none; color: #64748B; cursor: pointer; padding: 2px; line-height: 1;" onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    `;

    container.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
      toast.style.transform = "translateY(0)";
      toast.style.opacity = "1";
    }, 10);

    // Remove after 10 seconds
    setTimeout(() => {
      toast.style.transform = "translateY(-20px)";
      toast.style.opacity = "0";
      setTimeout(() => {
        toast.remove();
      }, 400);
    }, 10000);
  }
});

function hexToRgb(hex) {
  if (!hex) return '37, 99, 235';
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '37, 99, 235';
}

// Global menu cache to map item IDs to names in the Chef panel completed view
window.menuCacheGlobal = {};
function initGlobalMenuCache() {
  if (typeof firebase !== 'undefined' && window.db) {
    window.db.collection("menu").get().then(snap => {
      snap.forEach(doc => {
        const data = doc.data();
        window.menuCacheGlobal[data.item_id] = data.item_name;
      });
      window.dispatchEvent(new Event('chef-order-completed'));
    }).catch(e => console.error("Error loading global menu cache:", e));
  } else {
    setTimeout(initGlobalMenuCache, 500);
  }
}
initGlobalMenuCache();

function loadWaiterRecentOrders(staffId) {
  if (typeof firebase === 'undefined' || !window.db) {
    setTimeout(() => loadWaiterRecentOrders(staffId), 500);
    return;
  }
  
  window.db.collection("bills")
    .where("staff_id", "==", staffId)
    .onSnapshot(snapshot => {
      const userBills = [];
      snapshot.forEach(doc => {
        userBills.push({ id: doc.id, ...doc.data() });
      });
      
      // Sort client-side to avoid composite index requirement
      userBills.sort((a, b) => new Date(b.payment_time) - new Date(a.payment_time));
      const recent = userBills.slice(0, 5);
      
      const container = document.getElementById("waiter-recent-orders-container");
      if (!container) return;
      
      if (recent.length === 0) {
        container.innerHTML = `<p class="text-muted small text-center py-3">No orders completed yet.</p>`;
        return;
      }
      
      let html = '<div style="display: flex; flex-direction: column; gap: 8px; padding-bottom: 10px;">';
      recent.forEach(bill => {
        const timeStr = bill.payment_time ? new Date(bill.payment_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-';
        html += `
          <div class="recent-order-card" style="border-radius: 10px; padding: 10px 12px; font-size: 0.82em;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="font-weight-bold recent-order-title">Bill #${bill.bill_id}</span>
              <span class="badge bg-secondary text-white">Table ${bill.table_id}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.95em; margin-bottom: 4px;">
              <span>Rs ${parseFloat(bill.total_amount || 0).toFixed(2)}</span>
              <span>${timeStr}</span>
            </div>
            <div class="text-right" style="border-top: 1px solid rgba(128,128,128,0.15); padding-top: 4px;">
              <a href="../posBackend/receipt.html?bill_id=${bill.bill_id}" style="color: #EF4444; text-decoration: none; font-weight: 600; font-size: 0.9em; display: inline-flex; align-items: center; gap: 4px;">
                View Receipt <i class="fas fa-arrow-right" style="font-size: 0.8em;"></i>
              </a>
            </div>
          </div>
        `;
      });
      html += '</div>';
      container.innerHTML = html;
    }, err => {
      console.error("Error loading waiter recent orders:", err);
      const container = document.getElementById("waiter-recent-orders-container");
      if (container) {
        container.innerHTML = `<p class="text-danger small">Error loading orders.</p>`;
      }
    });
}

function loadChefRecentOrders(staffId) {
  const completedKey = `chef_completed_${staffId}`;
  
  function render() {
    let completed = [];
    try {
      completed = JSON.parse(localStorage.getItem(completedKey) || "[]");
    } catch(e) {}
    
    const container = document.getElementById("chef-recent-orders-container");
    if (!container) return;
    
    if (completed.length === 0) {
      container.innerHTML = `<p class="text-muted small text-center py-3">No completed orders yet.</p>`;
      return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 8px; padding-bottom: 10px;">';
    completed.forEach(order => {
      const timeStr = order.time_completed ? new Date(order.time_completed).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-';
      const itemName = (window.menuCacheGlobal && window.menuCacheGlobal[order.item_id]) || order.item_id;
      
      html += `
        <div class="recent-order-card" style="border-radius: 10px; padding: 10px 12px; font-size: 0.82em;">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="font-weight-bold recent-order-title">Kitchen #${order.kitchen_id}</span>
            <span class="badge bg-success text-white">Table ${order.table_no}</span>
          </div>
          <div style="font-weight: 600; margin-bottom: 4px;">
            ${itemName} <span style="color: #F59E0B; margin-left: 2px;">x${order.quantity}</span>
          </div>
          <div class="text-muted text-right" style="font-size: 0.85em; border-top: 1px solid rgba(128,128,128,0.15); padding-top: 4px;">
            <span><i class="fa fa-check-circle text-success mr-1"></i>${timeStr}</span>
          </div>
        </div>
      `;
    });
    html += '</div>';
    container.innerHTML = html;
  }
  
  render();
  window.addEventListener('chef-order-completed', render);
}
