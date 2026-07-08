// component-loader.js - Dynamic Header/Footer Loader and Auth Observer
(function() {
  document.documentElement.setAttribute('data-theme', 'light');
  document.documentElement.classList.add('preload');
})();

document.addEventListener("DOMContentLoaded", function () {
  const rootPath = window.location.origin;

  // 1. Fetch and inject Header if #header section is present (or prepend it to body)
  const headerContainer = document.getElementById("header");
  if (headerContainer) {
    fetch('/customerSide/components/header.html')
      .then(response => {
        if (!response.ok) throw new Error("Header not found");
        return response.text();
      })
      .then(html => {
        // Replace inner HTML of header
        headerContainer.outerHTML = extractBodyContent(html);
        initializeNavLinks();
        observeAuthState();
      })
      .catch(err => console.error("Error loading header:", err));
  }

  // 2. Fetch and inject Footer if #footer container is present (or append to body)
  const footerContainer = document.getElementById("footer");
  if (footerContainer) {
    fetch('/customerSide/components/footer.html')
      .then(response => {
        if (!response.ok) throw new Error("Footer not found");
        return response.text();
      })
      .then(html => {
        footerContainer.outerHTML = extractBodyContent(html);
      })
      .catch(err => console.error("Error loading footer:", err));
  }

  // Helper to extract clean content inside <body> to prevent double html/body tags
  function extractBodyContent(htmlString) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlString, 'text/html');
    const bodyContent = doc.body.innerHTML;
    return bodyContent || htmlString;
  }

  // Set active nav states or correct URL paths
  function initializeNavLinks() {
    const currentPath = window.location.pathname;
    
    // Adjust home/menu anchor links if we are NOT on home.html
    const isHome = currentPath.includes("home.html") || currentPath === "/" || currentPath.endsWith("/home/");
    
    const homeLink = document.getElementById("nav-home-link");
    const menuLink = document.getElementById("nav-menu-link");
    const aboutLink = document.getElementById("nav-about-link");
    const contactLink = document.getElementById("nav-contact-link");
    
    if (!isHome) {
      if (homeLink) homeLink.href = "/customerSide/home/home.html#hero";
      if (menuLink) menuLink.href = "/customerSide/home/home.html#projects";
      if (aboutLink) aboutLink.href = "/customerSide/home/home.html#about";
      if (contactLink) contactLink.href = "/customerSide/home/home.html#contact";
    }

    // Hamburger menu toggle (legacy compatibility)
    $(document).on("click", ".hamburger", function () {
      $(this).toggleClass("active");
      $(".nav-list ul").toggleClass("active");
    });
  }

  // Listen for user login/logout states in real-time
  function observeAuthState() {
    if (typeof firebase === 'undefined' || !window.auth) {
      console.warn("Firebase Auth not initialized yet, retrying in 500ms...");
      setTimeout(observeAuthState, 500);
      return;
    }

    window.auth.onAuthStateChanged(async function (user) {
      const dropdown = document.getElementById("nav-account-dropdown");
      if (!dropdown) return;

      if (user) {
        // User is logged in
        try {
          // In our seed script, accounts are stored in 'users' with their emails or account IDs
          // Firebase Authentication uses UIDs, so we'll look up by email to link legacy seed data or standard users
          const userQuery = await window.db.collection("users")
            .where("email", "==", user.email)
            .limit(1)
            .get();
          
          if (!userQuery.empty) {
            const userData = userQuery.docs[0].data();
            const userName = userData.name || userData.email.split('@')[0];
            
            // Store details locally for session persistence across pages
            localStorage.setItem("account_id", userData.account_id || user.uid);
            localStorage.setItem("user_name", userName);
            localStorage.setItem("user_role", userData.role || "Customer");

            dropdown.innerHTML = `
              <p class="logout-link-container" style="font-size:1.1em; padding:8px 16px; margin:0; color:white; font-weight:bold;">
                ${userName}
              </p>
              <hr style="border: 0; border-top: 1px solid #444; margin: 8px 0;">
              <a class="logout-link" id="nav-logout-btn" href="#" style="color: crimson; padding: 8px 16px; text-decoration: none; display: block; font-weight: bold;">Logout</a>
            `;

            // Attach logout event
            document.getElementById("nav-logout-btn").addEventListener("click", function (e) {
              e.preventDefault();
              window.auth.signOut().then(() => {
                localStorage.clear();
                window.location.href = "/customerSide/home/home.html";
              });
            });

            // Tooltip toggle hover
            $(".logout-link-container").hover(
              function () { $(this).find(".tooltip").fadeIn(100); },
              function () { $(this).find(".tooltip").fadeOut(100); }
            );

          } else {
            // Document not found in 'users' collection (newly signed up user)
            dropdown.innerHTML = `
              <p style="font-size:1.1em; padding:8px 16px; margin:0; color:white; font-weight:bold;">${user.email.split('@')[0]}</p>
              <a class="logout-link" id="nav-logout-btn" href="#" style="color: crimson; padding: 8px 16px; text-decoration: none; display: block;">Logout</a>
            `;
            document.getElementById("nav-logout-btn").addEventListener("click", function (e) {
              e.preventDefault();
              window.auth.signOut().then(() => {
                localStorage.clear();
                window.location.href = "/customerSide/home/home.html";
              });
            });
          }
        } catch (error) {
          console.error("Error loading user credentials:", error);
        }
      } else {
        // User is not logged in
        localStorage.clear();
        dropdown.innerHTML = `
          <a class="signin-link" href="/customerSide/customerLogin/register.html" style="color: white; font-size:14px; padding:10px 16px; display:block;">Sign Up</a>
          <a class="login-link" href="/customerSide/customerLogin/login.html" style="color: white; font-size:14px; padding:10px 16px; display:block;">Log In</a>
        `;
      }
    });
  }


  // Enable smooth transitions post render
  setTimeout(() => {
      document.documentElement.classList.remove('preload');
  }, 100);
});
