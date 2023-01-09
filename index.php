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
  <?php $HTM=$SET['data/'].$SET['them/'].$SET['shoptheme'].'/header.htm'; if (file_exists($HTM)) include($HTM); else include('ui/header.htm'); ?>
  <body>
    <div id="peer2product">
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
  <?php }
  $HTM=$SET['data/'].$SET['them/'].$SET['shoptheme'].'/footer.htm'; if (file_exists($HTM)) include($HTM); else include('ui/footer.htm'); ?>
</div>
  </body>
</html>
