<?php echo $header; ?>
<h1><?php echo $title ?></h1>
<?php foreach($posts as $post) { ?>
<article id="<?php echo $post['post_type']?>-<?php echo $post['post_id']?>" class="<?php echo $post['post_type']?>-<?php echo $post['post_id']?> <?php echo $post['post_type']?> type-<?php echo $post['post_type']?> status-publish format-standard category-uncategorized h-entry hentry h-as-article <?php echo ($post['draft'] == 1 ? 'draft':'') ?> <?php echo ($post['deleted'] == 1 ? 'deleted':'') ?>" itemprop="blogPost" itemscope="" itemtype="http://schema.org/BlogPosting">
  <header class="entry-header">
    <?php if($post['post_type'] != 'listen'){ ?>
    <h1 class="entry-title p-name" itemprop="name headline"><a href="<?php echo $post['permalink']?>" class="u-url url" title="Permalink to <?php echo $post['title']?>" rel="bookmark" itemprop="url"><?php echo $post['title']?></a></h1>
    <?php } ?>

        <div class="entry-meta">      
      <span class="sep">Posted on </span>
        <a href="<?php echo $post['permalink']?>" title="<?php echo date("g:i A", strtotime($post['timestamp']))?>" rel="bookmark" class="url u-url"> <time class="entry-date updated published dt-updated dt-published" datetime="<?php echo date("c", strtotime($post['timestamp']))?>" itemprop="dateModified"><?php echo date("F j, Y", strtotime($post['timestamp']))?></time> </a>
        <address class="byline"> <span class="sep"> by </span> <span class="author p-author vcard hcard h-card" itemprop="author" itemscope itemtype="http://schema.org/Person"><img alt='' src='<?php echo $author_image?>' class='u-photo avatar avatar-40 photo' height='40' width='40' /> <a class="url uid u-url u-uid fn p-name" href="<?php echo $post['author']['link']?>" title="<?php echo $post['author']['display_name']?>" rel="author" itemprop="url"><span itemprop="name"><?php echo $post['author']['display_name']?></span></a></span></address>
        <?php if($post['replyto']) { ?>
            <div class="repyto">
               In Reply To <a class="u-in-reply-to" rel="in-reply-to" href="<?php echo $post['replyto']?>">This</a>
            </div>
        <?php }  // end if replyto?>
        </div><!-- .entry-meta -->
      </header><!-- .entry-header -->

      <div class="entry-content e-content" itemprop="description articleBody">
        <?php if($post['image_file']) { ?>
            <img src="<?php echo $post['image_file']?>" class="u-photo photo-post" /><br>
        <?php } ?>
    <?php if($post['post_type'] != 'listen'){ ?>
      <?php echo $post['body_html']?>
      <?php  } else {
          echo 'I listend To <span class="song-title">'.$post['title'].'</span> by <span class="song-artist">'.$post['artist'].'</span>.';
      } ?>
      
      </div><!-- .entry-content -->
  
  <footer class="entry-meta">
    <?php if($post['comment_count'] > 0) { ?>
    <span class="comments-link"><a href="<?php echo $post['permalink']?>#comments" title="Comments for <?php echo $post['title']?>"><i class="fa fa-comment-o"></i> <?php echo $post['comment_count'] ?></a></span>
    <span class="sep"> | </span>
    <?php } ?>

    <?php if($post['like_count'] > 0) { ?>
    <span class="likes-link"><a href="<?php echo $post['permalink']?>#likes" title="Likes of <?php echo $post['title']?>"><i class="fa fa-heart-o"></i> <?php echo $post['like_count']?></a></span>
    <span class="sep"> | </span>
    <?php } ?>
  
  <?php if($post['categories']){ ?>
      <?php foreach($post['categories'] as $category) { ?>
          <span class="category-link"><a class="p-category" href="<?php echo $category['permalink']?>" title="<?php echo $category['name']?>"><?php echo $category['name']?></a></span>
  
      <?php } // end for post_categories as category ?>
  <?php } // end if post_categories ?>

  <?php if(!empty($post['syndications'])){ ?>
    <div class="syndications">
    <?php foreach($post['syndications'] as $elsewhere){ ?>

      <?php if(isset($elsewhere['image'])){ ?>
      <a class="u-syndication" href="<?php echo $elsewhere['syndication_url']?>" ><img src="<?php echo $elsewhere['image']?>" title="<?php echo $elsewhere['site_name']?>" /></a>
      <?php } else { ?>
      <a class="u-syndication" href="<?php echo $elsewhere['syndication_url']?>" ><i class="fa fa-link"></i></a>
      <?php } ?>
      
    <?php } //end foreach ?>
    </div>
  <?php } ?>
    <div class="admin-controls">
      <?php foreach($post['actions'] as $actiontype => $action){ ?>
      <indie-action do="<?php echo $actiontype?>" with="<?php echo $post['permalink']?>">
      <a href="<?php echo $action['link'] ?>" title="<?php echo $action['title']?>"><?php echo $action['icon']?></a>
      </indie-action>
      <?php } ?>
    </div>
  </footer><!-- #entry-meta --></article><!-- #post-<?php echo $post['post_id']?> -->

<?php } //end foreach posts ?>

<?php echo $footer; ?>