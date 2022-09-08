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
      <div id="footer">Powered by <a href="http://peer2product.com">Peer2Product</a></div>
    </div>
  </div>
    <?php include('ui/footer.htm'); ?>
  </body>
</html>
<?php } ?>
