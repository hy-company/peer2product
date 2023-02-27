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
      <?php $HTM=$SET['data/'].$SET['them/'].$SET['shoptheme'].'/navbar.htm'; if (file_exists($HTM)) include($HTM); else include('ui/navbar.htm');
            include('lib/main.php');?>
    </div>
  </div>
  <?php }
  $HTM=$SET['data/'].$SET['them/'].$SET['shoptheme'].'/footer.htm'; if (file_exists($HTM)) include($HTM); else include('ui/footer.htm'); ?>
</div>
  </body>
</html>
