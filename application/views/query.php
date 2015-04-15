<script src="<?= base_url('js/jquery.min.js') ?>"></script>
<script src="<?= base_url('js/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('js/tempo.min.js') ?>"></script>
<script src="<?= base_url('js/lib/d3/d3.js') ?>"></script>
<script src="<?= base_url('js/d3.layout.cloud.js') ?>"></script>
<script type="text/javascript">
    // Global Config Path
    var tweet_api_base = "<?= site_url('api/search') ?>";
    var query = "<?= $query ?>";
	var user_api_base = "<?= site_url('api/user') ?>";
</script>
<script type="text/javascript" src="<?= base_url('js/search.js') ?>"></script>
<script src="http://echarts.baidu.com/build/dist/echarts.js"></script>

<nav class="navbar navbar-default navbar-fixed-top">
	<div class="container-fluid">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="http://eest.webkdd.org/">
					<b>CHEEST</b>
				</a>
			</div>
			
			<div>
			  <form class="navbar-form navbar-right" role="search" action="<?= site_url('search/index') ?>" method="GET">
				<div class="input-group">
					<input id="q" type="text" class="form-control" name="q" placeholder="输入关键词" style="width:400px;">
					<span class="input-group-btn">
						<button id="search-btn" type="submit" class="btn btn-default"> <span class="glyphicon glyphicon-search" aria-hidden="true"></span> </button>
					</span>
				</div>
			  </form>
			</div>
		</div>
	</div>
</nav>


<div id="result-container" class="container" role="main">
	<div style="position:fixed;width:1100px;">
		<div class="row">
			<div class="col-md-7">
				<div role="tabpanel">
					<!-- Nav tabs -->
					<ul id="entity-source" class="nav nav-pills nav-justified" role="tablist" style="margin-bottom:20px">
						<li role="presentation" class="active">
							<a href="#news" aria-controls="news" role="tab" data-toggle="tab" id="a1">
								新闻实体
							</a>
						</li>
						<li role="presentation">
							<a href="#twitter" aria-controls="twitter" role="tab" data-toggle="tab" id="a4">
								微博实体
							</a>
						</li>
						<li role="presentation">
							<a href="#embedding" aria-controls="embedding" role="tab" data-toggle="tab" id="a2">
								词嵌入实体
							</a>
						</li>
						<!--
						<li role="presentation">
							<a href="#freebase" aria-controls="freebase" role="tab" data-toggle="tab" id="a3">
								知识库
							</a>
						</li>
						-->
					</ul>
					<!-- Tab panes -->
					<div class="tab-content">
						<div role="tabpanel" class="tab-pane fade in active" id="news">					
							<div id="loading-entity" >
								<div class="col-md-12">
									<center>
									<img width=80 src="<?= base_url('images/loading.gif') ?>" alt="loading entity" />
									</center>
								</div>
							</div>					
							<div id="newsEntity" style="height:700px;">
							</div>
						</div>
						
						<div role="tabpanel" class="tab-pane fade" id="twitter">
							<div id="twitterEntity" style="height:700px;">
							</div>
						</div>
						
						<div role="tabpanel" class="tab-pane fade" id="embedding">
							<div id="embeddingEntity" style="height:700px;">
							</div>
						</div>
						<div role="tabpanel" class="tab-pane fade" id="freebase">
							<div id="freebaseEntity" style="height:700px">
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-5">
			</div>
		</div>
	</div>
	<div class="row"> 
		<div class="col-md-7">
		</div>
		<div class="col-md-5">
		<ol class="breadcrumb">
			<li class="active">
				相关用户
			</li>
		</ol>
		<?php if ($user != null): ?>
			<div class="row" data-template>		
				<div id="user-info-panel" class="col-md-12">
					<div class="media">
						<a class="media-left" href="http://twitter.com/<?= $user->screen_name ?>" target="_blank">
						<img class="user-img" src="<?= $user->profile_image_url ?>" alt="user-profile-img" />
						</a>
						<div class="media-body" style="padding-left: 20px;">
							<h4 class="media-heading">
								<a href="http://twitter.com/<?= $user->screen_name ?>" target="_blank">
								<?= $user->name ?>
								</a>
							</h4>
							微博数: <?= $user->statuses_count ?> 
							<br /> 
							关注数: <?= $user->friends_count ?> 
							<br /> 
							粉丝数: <?= $user->followers_count ?>
							<br />
							<?php if ($user->location != ""): ?>
							地点: <?= $user->location ?>
							<?php endif; ?>
						</div> 
					</div>
				</div>
				
				<div class="col-md-12">
					<p class="entity-description">
					<blockquote>
					<?= $user->description ?>
					</blockquote>
					</p>
				</div>
			</div>
		<?php endif; ?>
		
		<!-- here is focused entity in twitter user !-->
		<div class="row">		
			<div id="user-list" style="display:none">
				<div  data-template>
					<div class="col-md-12">
						<div class="media">
							<a class="media-left" href="http://twitter.com/{{screen_name}}" target="_blank">
							<img class="user-img" src="<?= base_url('images/user-default.png') ?>" data-src="{{profile_image_url}}" alt="user-profile-img" />
							</a>
							<div class="media-body" style="padding-left: 20px;">
								<h4 class="media-heading">
									<a href="http://twitter.com/{{screen_name}}" target="_blank">
									{{name}}
									</a>
								</h4>
								微博数: {{statuses_count}}
								<br /> 
								关注数: {{friends_count}} 
								<br /> 
								粉丝数: {{followers_count}}  
								<br />
								地点: {{location}}
							</div> 
						</div>
					</div>							
					<div class="col-md-12">
						<p class="entity-description">
							<blockquote>{{description}}</blockquote>
						</p>
					</div>
				</div>
			</div>
		</div>
		
		
        <!-- Clustered Results using TTG, novelty detection/online cluster -->
        <!-- Next Page For fast reading -->
        <div id="tweet-list">
			<ol class="breadcrumb">
				<li class="active">
					关于 "<strong id="query-name"></strong>" 的微博
					<span id="focus-info">
						<strong id="focus-notes"></strong>
					</span>
				</li>
			</ol>
			<!-- here is center information -->
			<div id="parent-{{_tempo.index}}" class="tweet-div" data-template>
				<div data-if-interval>{{interval}}</div>
				<div class="media">
					<div class="media-left">
						<img class="avatar tweet-user-profile-small" src="<?= base_url('images/news-default.png') ?>" data-src="{{center.user.profile_image_url}}" alt="profile" width=64 height=64/>
					</div>
					<div class="media-body">
						<p>
							<a target="_blank" class="tweet-user-screen-name" href="https://twitter.com/{{center.user.screen_name}}">			
								{{center.user.name}}
							</a>
							<span class="tweet-time">{{center.created_at|date 'HH:mm YY/MM/DD}}</span>
						</p>
						<p>
						{{center.text}}
						</p>
					</div>
					<div class="media-right">
						<p>
							<span class="icon-bar-a child-style{{center.has_children}}" title="collapse"  style="float:right;">
								<span index="{{_tempo.index}}" class="collapse-tweet glyphicon glyphicon-collapse-down" aria-hidden="true"></span>
							</span>
						</p> 
					</div>
				</div>
			
				<!-- here is children information -->
				<div class="children row" data-template-for="children">
					<div class="col-md-12" style="border-left: 2px solid #5bc0de; margin-top: 4px; margin-right: 4px;">
						<p>
							<a target="_blank" class="tweet-user-screen-name" href="https://twitter.com/{{user.screen_name}}">{{user.name}}</a>
							<span class="tweet-time">{{created_at|date 'HH:mm YY/MM/DD}}</span>
						</p>
						<p>
						{{text}}
						</p>   
					</div>
				</div>
			</div>
		</div>

		<!-- end of information display -->
		
        <div id="loading-tweets" class="row">
            <div class="col-md-12">
            <center>
            <img width="80" src="<?= base_url('images/loading.gif') ?>" alt="loading tweets" />
            </center>
            </div>
        </div>
        <button id="load-btn" class="btn btn-block btn-info">加载更多</button>
        <br /><br />
    </div>
	
	</div>
</div>
