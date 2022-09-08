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
      <div id="shopnav" class="row">
        <div class="col-xs-12" id='shopbanner'>
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
          <p>Read about our terms of use, privacy statement and return policy</p>
        </div>

        <div class="col-xs-4">
          <a href="<?=$SITE;?>about">
          <center><i class="fa fa-users fa-2x" aria-hidden="true"></i></center>
          <h4><?=$STR['About'];?></h4></a>
          <p>Learn more about our business and the people behind it.</p>
        </div>

        <div class="col-xs-4">
          <a href="<?=$SITE;?>contact">
          <center><i class="fa fa-comments-o fa-2x" aria-hidden="true"></i></center>

          <h4><?=$STR['Contact'];?></h4></a>
          <p>We'd love to hear from you! Get in touch with us!</p>
        </div>
        <div class="col-xs-12"><br><br><br><br></div>
      </div>

      <div id="footer">Powered by <a href="http://peer2product.com">Peer2Product</a></div>
   
  <?php } ?>
  </footer>
    <?php include('ui/footer.htm'); ?>
  </body>
</html>

