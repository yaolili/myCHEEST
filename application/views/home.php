<div class="container" style="padding-bottom:50px;">
	<div class="row" style="padding-bottom:30px;">
		<div class="col-md-12" style="text-align:center;">
            <img src="<?= base_url('images/logo.png') ?>" alt="EEST" />
		</div>
	</div>

	<div class="row">
		<form id="search-form" role="search" action="<?= site_url('search/index') ?>" method="GET">
			<div class="col-md-2"></div>
			<div class="col-md-8">
				<div class="input-group">
					<input id="q" type="text" class="form-control span8" name="q" placeholder="输入关键词">
					<span class="input-group-btn">
						<button id="search-btn" type="submit" class="btn btn-default">Search</button>
					</span>
				</div>
			</div>			
			<div class="col-md-2"></div>
		</form>
	</div>
</div>


<div id="intro-container" class="container">
    <!-- Frequent Query -->
    <div class="row" style="text-align:center;margin-bottom:20px;">
        <div class="col-md-3">
            <a class="search-link" href="<?= site_url('search/index?q=周杰伦') ?>">
                <img width="200"  src="<?= base_url('images/2.jpg') ?>" alt="周杰伦" />
                <br>周杰伦 
            </a>
        </div>
        <div class="col-md-3">
            <a class="search-link" href="<?= site_url('search/index?q=谷歌眼镜') ?>">
                <img width="200"  src="<?= base_url('images/11.jpg') ?>" alt="Google Glass" />
                <br>谷歌眼镜 
            </a>
        </div>
        <div class="col-md-3">
            <a class="search-link" href="<?= site_url('search/index?q=范冰冰弟弟') ?>">
                <img width="200"  src="<?= base_url('images/4.jpg') ?>" alt="Avril" />
                <br> 
                范冰冰弟弟 
            </a>
        </div>
        <div class="col-md-3">
            <a class="search-link" href="<?= site_url('search/index?q=奥巴马') ?>">
                <img width="200"  src="<?= base_url('images/1.jpg') ?>" alt="Obama" />
                <br>奥巴马 
            </a>
        </div>
    </div>
</div>
