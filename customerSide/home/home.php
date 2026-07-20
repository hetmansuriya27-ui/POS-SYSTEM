<?php include_once('../components/header.php')?>
<style>
    #hero::after {
        background-color: transparent !important;
        opacity: 0 !important;
    }
    
    #hero h1 {
        font-family: 'Outfit', sans-serif !important;
        letter-spacing: -0.02em !important;
        text-transform: uppercase !important;
    }
    
    #hero h1 strong {
        font-weight: 800 !important;
    }
    
    /* Main Heading (X HOTEL) color override */
    #hero h1:nth-child(1) {
        animation: text_reveal_main 0.5s ease forwards !important;
        animation-delay: 0.5s !important;
        color: #1F2937 !important;
    }
    
    /* Sub Heading (ARTISAN DINING) color override */
    #hero h1:nth-child(2) {
        animation: text_reveal_sub 0.5s ease forwards !important;
        animation-delay: 1.5s !important;
        color: #111827 !important;
    }
    
    /* Selection text styles */
    #hero ::selection {
        background-color: #DC2626 !important;
        color: #FFFFFF !important;
    }
    #hero h1::selection, #hero h1 strong::selection, #hero h1 span::selection {
        background-color: #DC2626 !important;
        color: #FFFFFF !important;
    }
    
    /* Reveal sweep box accent color */
    #hero h1 span {
        background-color: #DC2626 !important;
    }
    
    #hero h1:nth-child(1) span {
        animation-delay: 0.1s !important;
    }
    
    #hero h1:nth-child(2) span {
        animation-delay: 1.0s !important;
    }
    
    @keyframes text_reveal_main {
        100% {
            color: #1F2937;
        }
    }
    
    @keyframes text_reveal_sub {
        100% {
            color: #111827;
        }
    }
    
    /* Accent button styling */
    .cta {
        font-family: 'Inter', sans-serif !important;
        font-weight: 600 !important;
        font-size: 1.8rem !important;
        border-radius: 12px !important;
        padding: 12px 30px !important;
        border: 2px solid #DC2626 !important;
        background: transparent !important;
        color: #111827 !important;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        margin-top: 30px;
    }
    
    .cta:hover {
        background: #DC2626 !important;
        color: white !important;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.25) !important;
    }
    
    .cta-primary {
        background-color: #DC2626 !important;
        border-color: #DC2626 !important;
        color: white !important;
        box-shadow: 0 4px 14px rgba(220, 38, 38, 0.15) !important;
    }
    
    .cta-primary:hover {
        background-color: transparent !important;
        color: #DC2626 !important;
        border-color: #DC2626 !important;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3) !important;
    }
</style>
<!-- Hero Section with Image Background and Text Overlay -->
<section id="hero" style="position: relative;">
    <img src="../image/artisan_dining.jpg" alt="Artisan Dining" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2;">
    <div class="hero container" style="position: relative; z-index: 1;">
        <div>
            <h1><strong>X HOTEL</strong><span></span></h1>
            <h1><strong>ARTISAN DINING</strong><span></span></h1>
            <a href="#projects" type="button" class="cta">MENU</a>
            <a href="../CustomerReservation/reservePage.php" type="button" class="cta cta-primary" style="margin-left: 10px;">RESERVATION</a>
        </div>
    </div>
</section>
<!-- End Hero Section -->
  
  
  
  <!-- menu Section -->
  <section id="projects">
    <div class="projects container">
      <div class="projects-header">
        <h1 class="section-title">Me<span>n</span>u</h1>
      </div>
     
        
       <select style="text-align:center;" id="menu-category" class="menu-category">
        <option value="blue">ALL ITEMS</option>
        <option value="green">DRINKS</option>
        <option value="red">SIDE DISHES</option>
        <option value="yellow">MAIN DISH</option>
      </select>
        
    <div class="green msg">
        <div></div>
      <div class="drinks">
           <h1 style="text-align:center">DRINKS</h1>
          <?php foreach ($drinks as $item): ?>
      <p>
        <span class="item-name"> <strong><?php echo $item['item_name']; ?></strong></span>
        <span class="item-price">Rs<?php echo $item['item_price']; ?></span><br>
        <hr>
      </p>
    <?php endforeach; ?>
      </div>
    </div>
      
      
    <div class="red msg">
        <div></div>
      <div class="sideDish">
           <h1 style="text-align:center">SIDE DISHES</h1>
          <?php foreach ($sides as $item): ?>
      <p>
        <span class="item-name"> <strong><?php echo $item['item_name']; ?></strong></span>
        <span class="item-price">Rs<?php echo $item['item_price']; ?></span><br>
        <hr>
      </p>
    <?php endforeach; ?>
      </div>
    </div>
        
      
      
    <div class="yellow msg"> 
     
        <div></div>
      <div class="mainDish">
           <h1 style="text-align:center;">MAIN DISH</h1>
          <?php foreach ($mainDishes as $item): ?>
      <p>
        <span class="item-name"> <strong><?php echo $item['item_name']; ?></strong></span>
        <span class="item-price">Rs<?php echo $item['item_price']; ?></span><br>
        <hr>
      </p>
    <?php endforeach; ?>
      </div>
    </div>
      
      
       <div class="blue msg">
          
      <div class="drinks">
           <h1 style="text-align:center">DRINKS</h1>
          <?php foreach ($drinks as $item): ?>
      <p>
        <span class="item-name"> <strong><?php echo $item['item_name']; ?></strong></span>
        <span class="item-price">Rs<?php echo $item['item_price']; ?></span><br>
        <hr>
      </p>
    <?php endforeach; ?>
      </div>

      <div class="sideDish">
           <h1 style="text-align:center">SIDE DISHES</h1>
          <?php foreach ($sides as $item): ?>
      <p>
        <span class="item-name"> <strong><?php echo $item['item_name']; ?></strong></span>
        <span class="item-price">Rs<?php echo $item['item_price']; ?></span><br>
        <hr>
      </p>
    <?php endforeach; ?>
      </div>
             
      <div class="mainDish">
           <h1 style="text-align:center">MAIN DISH</h1>
          <?php foreach ($mainDishes as $item): ?>
      <p>
        <span class="item-name"> <strong><?php echo $item['item_name']; ?></strong></span>
        <span class="item-price">Rs<?php echo $item['item_price']; ?></span><br>
        <hr>
      </p>
    <?php endforeach; ?>
      </div>
          
      </div>
    </div>
  </section>
  <!-- End menu Section -->


  
  <!-- About Section -->
<section id="about" ">
  <div class="about container">
    <div class="col-right">
        <h1 class="section-title" >About <span>Us</span></h1>
        <h2>X Hotel Company History:</h2>
 <p>X Hotel is a well-established Western food establishment in the city's heart. X Hotel has become a popular choice for customers looking to celebrate special occasions or simply enjoy a relaxing meal, with a focus on providing delicious meals and a friendly dining experience.
 </p>
 <p>X Hotel, as a Western restaurant, offers a diverse menu that caters to a variety of tastes. The menu includes a wide range of options such as bar bites, salads, soups and a variety of main courses. Customers can savour succulent options such as steak and ribs, chicken, lamb, seafood, burgers and sandwiches, pasta, and a variety of delectable side dishes. The menu has been carefully curated to offer a balance of classic favourites and innovative creations, ensuring that every palate is satisfied.
 </p>
 <p>X Hotel's ability to accommodate customers is one of its distinguishing features. X Hotel strives to create an inviting and comfortable dining environment, whether guests prefer to walk in or make reservations in advance. The restaurant recognises the significance of creating memorable experiences, particularly for those celebrating special occasions. X Hotel is a popular choice for families, couples, and groups of friends because of its attentive staff and welcoming atmosphere.
 </p>
 <p>X Hotel has an inviting outdoor bar that is open seven days a week from 11:00 AM to 10:00 PM in addition to the indoor dining area.This outdoor space provides a relaxed setting for patrons to unwind and socialise while sipping on their favourite drinks and nibbling on bar bites. The bar serves a wide range of beverages, including cocktails, wines, beers and non-alcoholic options.
 </p>
    
       </div>
    </div>
  </section>
  <!-- End About Section -->
  
  
 <!-- Contact Section -->
<section id="contact">
  <div class="contact container">
    <div>
      <h1 class="section-title">Contact <span>info</span></h1>
    </div>
    <div class="contact-items">
      <div class="contact-item contact-item-bg">
        <div class="contact-info">
          <div class='icon'><img src="../image/icons8-phone-100.png" alt=""/></div>
          <h1>Phone</h1>
          <h2>+60 886 8786</h2>
        </div>
      </div>
      
      <div class="contact-item contact-item-bg"> 
        <div class="contact-info">
          <div class='icon'><img src="../image/icons8-email-100.png" alt=""/></div>
          <h1>Email</h1>
          <h2>contact@xhotel.com</h2> 
        </div>
      </div>
      
      <div class="contact-item contact-item-bg">
        <div class="contact-info">
          <div class='icon'> <img src="../image/icons8-home-address-100.png" alt=""/></div>
          <h1>Address</h1>
          <h2>Lot 62, Third Floor, Jalan Newton, No.345, Lorong Kluang, Kota Kinabalu, Malaysia, 88000</h2>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- End Contact Section -->

<?php 
include_once('../components/footer.php');
?>