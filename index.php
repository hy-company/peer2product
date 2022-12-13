<?php
  include('lib/init.php');
  // if going through payment gateway forwarding, do not display html
  if(isset($_GET['q']) || (isset($_POST['next']) && $_POST['next']=='Pay')) {
    include('lib/main.php');
  } else {
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <?php include('ui/header.htm'); ?>
  <body>
    <div id="peer2product">
<<<<<<< HEAD
      <div class="row">
        <div class="col-xs-12" id='shopbanner'>
          <a href="<?=$SITE;?>"><img class='img-responsive' src="<?php echo ($SET['shopmainlogo']?$SET['data/'].$SET['shopmainlogo']:$DEF['shopmainlogo']); ?>"/></a>
        </div>
      </div>
      <div class="row">
        <div id="navbar">
          <div class="col-xs-12">
            <a href="<?=$SITE;?>admin" target="_blank" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'" style="display: block;opacity: 0;transition: opacity 3s ease 0s;margin-top: -3em;">&#9881;</a>
          </div>
          <div class="col-xs-4">
            <a href="<?=$SITE;?>"><i class="fa-solid fa-store icon"></i><br><?=$STR['Store'];?></a>
          </div>
          <div class="col-xs-4">
           <a style="cursor: pointer;" onclick="toggleCart();"><i class="fa-solid fa-shopping-cart icon" id="arrowIcon"></i><br><?=$STR['Cart'];?></a>
          </div>
          <div class="col-xs-4">
            <a href="<?=$SITE;?>checkout"><i class="fa-solid fa-money-bill icon"></i><br><?=$STR['Checkout'];?></a>
          </div>
        </div>
      
      <?php include('lib/main.php');?>
  
    </div>
  </div>
  <?php } ?>

<?php include('ui/footer.htm'); ?>

</div> 
=======
      <div id="shopnav" class="row">
        <div class="col-md-12" id='shopbanner'>
          <a href="<?=$SITE;?>"><img class='img-responsive' src="<?php echo ($SET['shopmainlogo']?$SET['data/'].$SET['shopmainlogo']:$DEF['shopmainlogo']); ?>"/></a>
        </div>
        <?php include('ui/navbar.htm'); ?>
        
        
      </div>
      <?php include('lib/main.php');?>
      </div>
  </div>
      <footer>
      <div class="row">
        <div class="col-xs-12"><br></div>
        <div class="col-xs-4">
        <a href="<?=$SITE;?>terms">
          <center><i class="fa fa-asterisk fa-2x" aria-hidden="true"></i></center>  
          <h4><?=$STR['Terms'];?></h4></a>
          <p><?=$STR['footer_terms'];?></p>
        </div>

        <div class="col-xs-4">
          <a href="<?=$SITE;?>about">
          <center><i class="fa fa-users fa-2x" aria-hidden="true"></i></center>
          <h4><?=$STR['About'];?></h4></a>
          <p><?=$STR['footer_about'];?></p>
        </div>

        <div class="col-xs-4">
          <a href="<?=$SITE;?>contact">
          <center><i class="fa fa-comments-o fa-2x" aria-hidden="true"></i></center>

          <h4><?=$STR['Contact'];?></h4></a>
          <p><?=$STR['footer_contact'];?></p>
        </div>
        <div class="col-xs-12"><br><br><br><br></div>
        <p id="footer">Powered by <a href="http://peer2product.com">Peer2Product</a></p>
        <br>
      </div>

    
   
  <?php } ?>
  </footer>
    <?php include('ui/footer.htm'); ?>
>>>>>>> beta
  </body>
</html>

