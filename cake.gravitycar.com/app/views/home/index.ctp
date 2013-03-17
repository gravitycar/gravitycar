<h1 id="pageTitle" name="pageTitle"></h1>
<div id="homePageContents" name="homePageContents">
<div class="articleText" id="welcome" onClick="window.location='article.php?id=1';"><?=$whoami?></div>

<div class="articleText" id="resume" onClick="window.location='resources/resume_2011.pdf';"><?=$resume?></div>

<div class="articleText" id="blog" onClick="window.location='blog.php';"><?=$blogEntry?></div>

<div class="articleText" id="exhortation"><?=$exhortation?></div>
</div>


<div id="skinBackground">
  <div id="skinsContainer">
    <div id="skinCount" class="skinDiv"><?=$skinsCount?></div>
    <?=$skins?>
  </div>
</div>
