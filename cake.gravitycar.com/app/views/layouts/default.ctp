<?php


   function determineCSSPath($cssPath = "")
   {
      $defaultCssPath = "default";
      if ($cssPath == "")
      {
         $cssPath = $defaultCssPath;
         
         if (IsSet($_GET['cssPath']))
         {
            $cssPath = $_GET['cssPath'];
         }
         else if (IsSet($_SESSION['cssPath']))
         {
            $cssPath = $_SESSION['cssPath'];
         }
         else if (IsSet($_COOKIE['cssPath']))
         {
            $cssPath = $_COOKIE['cssPath'];
         }
      }
      
      $_SESSION['cssPath'] = $cssPath;
      setcookie('cssPath', $cssPath, mktime(time() + (60 * 60 * 24 * 7)));
      
      return $cssPath;
   }

//$cssPath = empty($_GET['cssPath']) ? "default" : $_GET['cssPath'];
$cssPath = determineCSSPath();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>Gravitycar.com - Mike Andersen's Web Site</title>
	<link href="http://www.gravitycar.com/css/<?=$cssPath?>/main.css" type="text/css" rel="stylesheet" />
	<link href="http://www.gravitycar.com/css/<?=$cssPath?>/nav.css" type="text/css" rel="stylesheet" />
	<link href="http://www.gravitycar.com/favicon.ico" rel="Shortcut Icon" />
	<link href="http://www.gravitycar.com/css/<?=$cssPath?>/home.css" type="text/css" rel="stylesheet" />
	<link href="http://www.gravitycar.com/css/<?=$cssPath?>/skinner.css" type="text/css" rel="stylesheet" />
</head>

<body>

<div id="navContainer" name="navContainer">
<ul id="navList" class="navList" name="navList">

	<li id="navhomeItem" class="topMenuItem" name="navhomeItem"><a id="navhomeLink" href="index.php" class="topMenuLink" name="navhomeLink">Home</a>
<ul id="navhome" class="subMenu" name="navhome">

	<li id="navloginItem" class="subMenuItem" name="navloginItem"><a id="navloginLink" href="login.php" class="subMenuLink" name="navloginLink">Log In</a></li>

</ul>
</li>
	<li id="aboutItem" class="topMenuItem" name="aboutItem"><a id="aboutLink" href="article.php?id=1" class="topMenuLink" name="aboutLink">About Me</a>
<ul id="about" class="subMenu" name="about">

	<li id="navresume(pdf)Item" class="subMenuItem" name="navresume(pdf)Item"><a id="navresume(pdf)Link" href="resources/resume_2010.pdf" class="subMenuLink" name="navresume(pdf)Link">Resume (pdf)</a></li>
	<li id="navaboutthesiteItem" class="subMenuItem" name="navaboutthesiteItem"><a id="navaboutthesiteLink" href="article.php?id=2" class="subMenuLink" name="navaboutthesiteLink">About the Site</a></li>
	<li id="navblogItem" class="subMenuItem" name="navblogItem"><a id="navblogLink" href="blog.php" class="subMenuLink" name="navblogLink">Blog</a></li>
	<li id="navgravitycar?Item" class="subMenuItem" name="navgravitycar?Item"><a id="navgravitycar?Link" href="article.php?id=11" class="subMenuLink" name="navgravitycar?Link">Gravity Car?</a></li>

</ul>
</li>
	<li id="mqtItem" class="topMenuItem" name="mqtItem"><a id="mqtLink" href="movieQuoteGame.php" class="topMenuLink" name="mqtLink">Movie Quotes!</a>
<ul id="mqt" class="subMenu" name="mqt">

	<li id="navplaymoviequotetriviaItem" class="subMenuItem" name="navplaymoviequotetriviaItem"><a id="navplaymoviequotetriviaLink" href="movieQuoteGame.php" class="subMenuLink" name="navplaymoviequotetriviaLink">Play Movie Quote Trivia</a></li>
</ul>
</li>
	<li id="cogItem" class="topMenuItem" name="cogItem"><a id="cogLink" href="eventLogin.php" class="topMenuLink" name="cogLink">Event Organizer</a></li>
	<li id="giftsItem" class="topMenuItem" name="giftsItem"><a id="giftsLink" href="giftList.php" class="topMenuLink" name="giftsLink">Gifts</a></li>

	<li id="usersItem" class="topMenuItem" name="usersItem"><a id="usersLink" class="topMenuLink" name="usersLink">Skins</a>
<ul id="users" class="subMenu" name="users">

	<li id="navgetitingear!Item" class="subMenuItem" name="navgetitingear!Item"><a id="navgetitingear!Link" href="/index.php?cssPath=gears" class="subMenuLink" name="navgetitingear!Link">Get It in Gear!</a></li>
	<li id="navsupertokyoItem" class="subMenuItem" name="navsupertokyoItem"><a id="navsupertokyoLink" href="/index.php?cssPath=tokyo" class="subMenuLink" name="navsupertokyoLink">Super Tokyo</a></li>
	<li id="navthetapItem" class="subMenuItem" name="navthetapItem"><a id="navthetapLink" href="/index.php?cssPath=tap" class="subMenuLink" name="navthetapLink">The Tap</a></li>
</ul>
</li>
</ul>

</div>

<div class="loginDiv" id="loginDiv" name="loginDiv"><a href="login.php">Log In</a></div>

<?php echo $content_for_layout ?>

<div class="footer"><a class="left" href="mailto:">mike@gravitycar.com</a>
<div class="right">408-264-4044</div>

<div class="middle">Copyright Mike Andersen 2011</div>

</div>

<script src="js/addOnLoad.js" type="text/javascript" lang="JavaScript"></script>
<script src="js/ieMenuFix.js" type="text/javascript" lang="JavaScript"></script>
<script src="js/jquery.js" type="text/javascript" lang="JavaScript"></script>
<script src="js/comment.js" type="text/javascript" lang="JavaScript"></script>

</body>
</html>
