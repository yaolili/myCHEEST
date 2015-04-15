$(function(){

	//echart
	require.config({
		paths: {
			echarts: 'http://echarts.baidu.com/build/dist'
		}
	});

    var news_query;
    var newsdata, tweetsdata, newsentity, embeddingentity;
    var templateTweet = Tempo.prepare("tweet-list", {'escape': false});
    //var templateNews = Tempo.prepare("goolge-news-list", {'escape': false});
	//var templateTwitterEntity = Tempo.prepare("twitter-entity-list", {'escape': false});
	//var templateFreebase = Tempo.prepare("freebase-entity-list", {'escape': false});
	var templateUser = Tempo.prepare("user-list", {'escape': false});
	var freebasedata = new Array();
	var userdata = new Array();
	var twitteruser = new Array();
	var twittertext = new Array();
	
	function getTimeInterval(tweetsdata, num){
		var newData = new Array();
		var time = new Date();
		//get total number of each interval type
		var intervalType = [0, 0, 0, 0, 0, 0, 0];
		var begin = new Date(tweetsdata[0]["center"]["created_at"]);
		var end = new Date(tweetsdata[tweetsdata.length - 1]["center"]["created_at"]);
		var step = Math.round(( begin.getTime() - end.getTime() ) / 420000);
		var firstTime = Math.round((time.getTime() - begin.getTime()) / 60000);
		console.log("step is:" + step + "; firstTime is:" + firstTime + "min");	
		
		//get cordinate data
		var cordinateData = new Array();
		if(!step || (step == 1)){
			for(var i = 0; i < 7; i++) cordinateData[i] = (time.getMinutes() - begin.getMinutes() + i) + "min";
		}
		else{
			cordinateData[0] = firstTime + "-" + (firstTime+step) + "min";
			for(var i = 1; i < 6; i++) cordinateData[i] = (firstTime+step*i+1) + "-" + (firstTime+step*(i+1)) + "min";
			cordinateData[6] = "more than " + (firstTime+6*step) + "min";
		}
		

		//get number of each cordinate data
		for(i = 0; i < tweetsdata.length; i++){
			var dataTime = new Date(tweetsdata[i]["center"]["created_at"]);
			var interval = Math.round((begin.getTime() - dataTime.getTime()) / 60000);
			//step ==0 means time interval is less than 7 min
			if( !step) intervalType[begin.getMinutes() - dataTime.getMinutes()]++;
			else{
				if( interval <= step){
					intervalType[0]++;
				}
				else if( interval <= 2 * step){
					intervalType[1]++;
				}
				else if( interval <= 3 * step){
					intervalType[2]++;
				}
				else if( interval <= 4 * step){
					intervalType[3]++;
				}
				else if( interval <= 5 * step){
					intervalType[4]++;
				}
				else if( interval <= 6 * step){
					intervalType[5]++;
				}
				else{
					intervalType[6]++;
				}
			}
		}
		
		
		for(var i = 0; i < 7; i++)
			console.log("cordinateData[" + i + "] is:" + cordinateData[i] + ",number is:" +intervalType[i]); 
			
		
		//get newData with time interval information
		for(var i = 0; i < tweetsdata.length; i++){
			dataTime = new Date(tweetsdata[i]["center"]["created_at"]);
			var interval2 = time.getMinutes() - dataTime.getMinutes() ;
			if( (time.getTime() - dataTime.getTime()) / 1000 > 240) {
				newData[i]                          = "—— 多于3分钟 ———————————————";
			}
			else{
				if( interval2 == 0 ) newData[i]     = "—— 刚刚 ————————————————————";
				else if( interval2 == 1) newData[i] = "—— 1分钟 ———————————————————";
				else if( interval2 == 2) newData[i] = "—— 2分钟 ———————————————————";
				else if( interval2 == 3) newData[i] = "—— 3分钟 ———————————————————";
				else newData[i]                     = "—— 多于3分钟 ———————————————";
			}
		}	
		
		//delete duplicated intervals
		for( i = (tweetsdata.length - 1); i > 0; i--){
			if(newData[i] != newData[i-1]) tweetsdata[i]["interval"] = newData[i];	
			else tweetsdata[i]["interval"] = "";
		}
		tweetsdata[0]["interval"] = newData[0];

		return tweetsdata;
	}
	
	//autoLoad more tweetsdata without click "Load more" button
	function autoLoad(cluster_num, tweetsdata){
		var current_num = cluster_num;
		for(var i = 0; i < tweetsdata.length; i++){
			if(tweetsdata[i]["center"]["has_children"]){
				current_num++;
			}
		}
		console.log("current_num is:" + current_num);
		if(current_num < 10) {
			setTimeout(function(){
				$("#load-btn").trigger("click");
			}, 5000);
			//autoLoad must be delayed until trigger updated tweetsdata2
			setTimeout(function() {
				autoLoad(current_num, tweetsdata2);
			},10000);
		}
		return ;
	}
	
	var focusEntity = function(entity) {
		/* property = $(this).attr("property");
		id = $(this).attr("index");
		new_query = query + " " + newsentity[property][id]; */
		new_query = query + " " + entity;
		$("#focus-notes").html(" + \""+entity+"\"");
		$("#focus-info").show();
		searchQueryTweets(new_query);
		getUser(entity);
	};
	
  	
	//get news entities
	var getNewsEntities = function(query) {		
		$.ajax({
			//url: "http://eest.webkdd.org/ner/index.php?source=news&q=" + query + "&callback=?",	
			url: "http://eest.webkdd.org/CHEEST/index.php/api/nerTagger?q=" + query + "&source=news&callback=?",
			type: "GET",
			dataType: "jsonp",
			success: function(data) {
				newsentity = data;				
									
				//here to add echart
				require(
				[
					'echarts',
					'echarts/theme/macarons',  
					'echarts/chart/force', 
					'echarts/chart/chord',
				],
				function (ec, theme) {
					// 基于准备好的dom，初始化echarts图表
					var myChart = ec.init(document.getElementById('newsEntity'), theme); 
					var option = {
						title : {
							text: '',
							x:'left',
							y:'top'
						},
						tooltip : {
							trigger: 'item',
							textStyle : {
								color: 'white',
								decoration: 'none',
								align: 'left',
								fontSize: 5,
								fontWeight: 'normal'
							}, 
							//formatter: '{a} : {b}'
							formatter: function (params,ticket,callback) {
								//for(var property in params)								
									//console.log("params  property is:" + property + ", value is:" + params[property]);
								for(var property in params["data"])								
									console.log("params->data property is:" + property + ", value is:" + params["data"][property]);
								if(params["data"]["title"]){
									//entity node
									var time = params["data"]["time"];
									var title = params["data"]["title"];
									var content = params["data"]["content"];
									var res = "<div style=\"width:300px; white-space:normal; line-height:15px;\">";
									for(var i = 0; i < time.length; i++){
										res += "<p style=\"color:#ff0000;font-weight:bold;\">" + title[i] + "</p>"
										res += "<p>" + time[i] + "</p>"
										res += "<p>" + content[i] +"</p>";
									}
									res += "</div>";
													
								}
								else if(params["data"]["name"]){
									//center node
									var res = params["data"]["name"];
								}
								else{
									//edge
									var res = params["data"]["target"] + " - " + params["data"]["source"];
								}
								setTimeout(function (){
									// 仅为了模拟异步回调
									callback(ticket, res);
								}, 1)
								return 'loading'; 
							}
						},
						toolbox: {
							show : true,
							feature : {
								restore : {title: '还原', show: true},
								//magicType: {title:{force: 'force', chord: 'chord'}, show: true, type: ['force', 'chord']},
								saveAsImage : {title: '保存图片', show: true}
							}
						},
						legend: {
							x: 'left',
							data:['人物','组织','地点','地缘政治实体']
						},
						series : [
							{
								type:'force',
								//name : "人物关系",
								ribbonType: false,
								categories : [
									{
										name: 'query'
									},
									{
										name: '人物'
									},
									{
										name: '组织'
									},
									{
										name: '地点'
									},
									{
										name: '地缘政治实体'
									}
								],
								itemStyle: {
									normal: {
										label: {
											show: true,
											textStyle: {
												color: '#333'
											}
										},
										nodeStyle : {
											brushType : 'both',
											borderColor : 'rgba(255,215,0,0.4)',
											borderWidth : 1
										},
										linkStyle: {
											type: 'curve'
										}
									},
									emphasis: {
										label: {
											show: false
											// textStyle: null      // 默认使用全局文本样式，详见TEXTSTYLE
										},
										nodeStyle : {
											//r: 30
										},
										linkStyle : {}
									}
								},
								useWorker: false,
								minRadius : 15,
								maxRadius : 25,
								gravity: 1.1,
								scaling: 1.1,
								roam: 'move',
								nodes:[
									{category:0, name: query, label:  query,  symbolSize: 40},
								],
								links : [
									//{source : '1', target : '乔布斯', weight : 2, name: '合伙人'},
								]
							}
						]
					};
					var ecConfig = require('echarts/config');

					myChart.on(ecConfig.EVENT.FORCE_LAYOUT_END, function () {
						console.log(myChart.chart.force.getPosition());
					});
					
					if(newsentity){
						for(var property in newsentity){						
							//here to add event child nodes fixed												
							switch(property){
								case "person":
									var style = 1;
									break;
								case "organization":
									var style = 2;
									break;
								case "location":
									var style = 3;
									break;
								case "gpe":
									var style = 4;
									break;
							}	
							//console.log(" newsentity[property]  is:" +property);
							for(var entity in newsentity[property]){
								var time = new Array();
								var title = new Array();
								var content = new Array();
								for(var i = 0; i < newsentity[property][entity].length; i++){
									console.log("newsentity property is:" + property +", entity is :" + entity +"length is:"+ newsentity[property][entity][i]);
									if(i % 3 == 0) time.push(newsentity[property][entity][i]);
									else if(i % 3 == 1) title.push(newsentity[property][entity][i]);
									else content.push(newsentity[property][entity][i]);
								}
								
								option.series[0].nodes.push({
									category: style, 
									name: entity, 
									time: time,
									title: title,
									content: content,
									symbolSize: 20
								});
								option.series[0].links.push({source: entity, target: query});
							}																	
						}
					}
															
					myChart.setOption(option); 
					
					//动态展开event节点
					function focus(param) {
						var data = param.data;
						var links = option.series[0].links;
						var nodes = option.series[0].nodes;
						if (
							data.source !== undefined
							&& data.target !== undefined
						) { //点击的是边
							var sourceNode = nodes.filter(function (n) {return n.name == data.source})[0];
							var targetNode = nodes.filter(function (n) {return n.name == data.target})[0];
							console.log("选中了边 " + sourceNode.name + ' -> ' + targetNode.name);
						} else { // 点击的是点
							console.log("选中了" + data.name );
							if(data.category) focusEntity(data.name);
						}
					}
					myChart.on(ecConfig.EVENT.CLICK, focus);					
				});	
				$("#loading-entity").hide();					
			},
			error: function(data){
				var output = '';
				for(var property in data){
					output += property + ':' + data[property] + ';';
				}
				console.log('[log] error information is:' + output);			
			}
		});
	};
			
	// get the w2v entities using word2vec
	var getEmbeddingEntities = function(query) {
		console.log("[log] w2v entities of " + query);
		$.ajax({
			//Also works without "&callback=?"
			//url: "http://172.31.222.115/test.php?q=\"" + query + "\"&callback=?",
			url: "http://webkdd.org/CHEEST_W2V/test.php?q=\"" + query + "\"&callback=?",			
			type: "GET",
			dataType: "jsonp",
			success: function(data) {
				embeddingentity = data;
				console.log("embedding data is:" + embeddingentity);
				$("#a2").click(function(){
					$("#embedding").addClass("active");				
					require(
					[
						'echarts',
						'echarts/theme/macarons',  
						'echarts/chart/force', 
						'echarts/chart/chord',
					],
					function (ec, theme) {
						// 基于准备好的dom，初始化echarts图表
						var myChart1 = ec.init(document.getElementById('embeddingEntity'), theme); 
						var option = {
							title : {
								text: '',
								x:'left',
								y:'top'
							},
							tooltip : {
								trigger: 'item',
								textStyle : {
									color: 'white',
									decoration: 'none',
									align: 'left',
									fontSize: 5,
									fontWeight: 'normal'
								}, 
								//formatter: '{b} : {c}'
								formatter: function (params,ticket,callback) {									
									for(var property in params["data"])								
										console.log("embedding params->data property is:" + property + ", value is:" + params["data"][property]);
									if(params["data"]["name"] == query){
										//center node
										var res = params["data"]["name"];
									}else if(params["data"]["name"]){
										//entity node
										var res = params["data"]["name"] + " : " + params["data"]["value"];
									}else{
										//edge
										var res = params["data"]["target"] + " - " + params["data"]["source"];
									}
									setTimeout(function (){
										// 仅为了模拟异步回调
										callback(ticket, res);
									}, 1)
									return 'loading'; 
								} 
							},
							toolbox: {
								show : true,
								feature : {
									restore : {title: '还原', show: true},
									saveAsImage : {title: '保存图片', show: true}
								}
							},
							legend: {
								x: 'left',
								data:['实体']
							},
							series : [
								{
									type:'force',
									ribbonType: false,
									categories : [
										{
											name: 'query'
										},
										{
											name: '实体'
										}
									],
									itemStyle: {
										normal: {
											label: {
												show: true,
												textStyle: {
													color: '#333'
												}
											},
											nodeStyle : {
												brushType : 'both',
												borderColor : 'rgba(255,215,0,0.4)',
												borderWidth : 1
											},
											linkStyle: {
												type: 'curve'
											}
										},
										emphasis: {
											label: {
												show: false
											},
											nodeStyle : {
											},
											linkStyle : {}
										}
									},
									useWorker: false,
									minRadius : 15,
									maxRadius : 25,
									gravity: 1.1,
									scaling: 1.1,
									roam: 'move',
									nodes:[
										{category:0, name: query, label: query, value: 1, symbolSize: 40},
									],
									links : [
									]
								}
							]
						};
						var ecConfig = require('echarts/config');					
						
						if(embeddingentity){
							for(var i = 0 ; i < embeddingentity.length; i++){ 
								
								option.series[0].nodes.push({
										category: 1, 
										name: embeddingentity[i][0], 
										value: embeddingentity[i][1],
										symbolSize: 20
									});
								option.series[0].links.push({source: embeddingentity[i][0], target: query});						
							}		
						}
																																					
						myChart1.setOption(option); 
						
						//动态展开event节点
						function focus(param) {
							var data = param.data;
							var links = option.series[0].links;
							var nodes = option.series[0].nodes;
							if (
								data.source !== undefined
								&& data.target !== undefined
							) { //点击的是边
								var sourceNode = nodes.filter(function (n) {return n.name == data.source})[0];
								var targetNode = nodes.filter(function (n) {return n.name == data.target})[0];
								console.log("选中了边 " + sourceNode.name + ' -> ' + targetNode.name);
							} else { // 点击的是点
								console.log("选中了" + data.name );
								if(data.category) focusEntity(data.name);
							}
						}
						myChart1.on(ecConfig.EVENT.CLICK, focus);					
					});	
				});
			},
			error: function(data){
				var output = '';
				for(var property in data){
					output += property + ':' + data[property] + ';';
				}
				console.log('[log] error information is:' + output);	
			}
		});
	};
		
	
	var getTwitterEntities = function(query){
		getUser(query);
		$.ajax({
			//url: "http://eest.webkdd.org/ner/index.php?source=twitter&q=" + query + "&callback=?",	
			url: "http://eest.webkdd.org/CHEEST/index.php/api/nerTagger?q=" + query + "&source=twitter&callback=?",
			type: "GET",
			dataType: "jsonp",
			success: function(data) {
				
				$("#a4").click(function(){
					$("#twitter").addClass("active");
	
					require(
					[
						'echarts',
						'echarts/theme/macarons',  
						'echarts/chart/force', 
						'echarts/chart/chord',
					],
					function (ec, theme) {
						// 基于准备好的dom，初始化echarts图表
						var myChart1 = ec.init(document.getElementById('twitterEntity'), theme); 
						var option = {
							title : {
								text: '',
								x:'left',
								y:'top'
							},
							tooltip : {
								trigger: 'item',
								textStyle : {
									color: 'white',
									decoration: 'none',
									align: 'left',
									fontSize: 5,
									fontWeight: 'normal'
								}, 
								formatter: '{b} : {c}'
							},
							toolbox: {
								show : true,
								feature : {
									restore : {title: '还原', show: true},
									saveAsImage : {title: '保存图片', show: true}
								}
							},
							legend: {
								x: 'left',
								data:['微博用户实体', '微博文本实体']
							},
							series : [
								{
									type:'force',
									ribbonType: false,
									categories : [
										{
											name: 'query'
										},
										{
											name: '微博用户实体'
										},
										{
											name: '微博文本实体'
										},

									],
									itemStyle: {
										normal: {
											label: {
												show: true,
												textStyle: {
													color: '#333'
												}
											},
											nodeStyle : {
												brushType : 'both',
												borderColor : 'rgba(255,215,0,0.4)',
												borderWidth : 1
											},
											linkStyle: {
												type: 'curve'
											}
										},
										emphasis: {
											label: {
												show: false
											},
											nodeStyle : {
											},
											linkStyle : {}
										}
									},
									useWorker: false,
									minRadius : 15,
									maxRadius : 25,
									gravity: 1.1,
									scaling: 1.1,
									roam: 'move',
									nodes:[
										{category:0, name: query, label: query, value: 1, symbolSize: 40},
									],
									links : [
									]
								}
							]
						};
						var ecConfig = require('echarts/config');					
						
						if(data){
							for(var i = 0 ; i < data.length; i++){					
								for(var property in data[i]){
									for(var entity in data[i][property]){
										console.log("entity is:" + entity);
										twittertext.push(entity);
									}
								}
							}
							
							for(var i = 0; i < twittertext.length; i++){
								option.series[0].nodes.push({
									category: 2, 
									name: twittertext[i], 
									value: 1,
									symbolSize: 20
								});
								option.series[0].links.push({source: twittertext[i], target: query});
							}
						}
						if(twitteruser){
							for(var i = 0; i < twitteruser.length; i++){
								option.series[0].nodes.push({
									category: 1, 
									name: twitteruser[i], 
									value: 1,
									symbolSize: 20
								});
								option.series[0].links.push({source: twitteruser[i], target: query});
							}
						}
						console.log("in echart, twitteruser is:" + twitteruser + ", twitter text is:" + twittertext);
																																																						
						myChart1.setOption(option); 
						
						//动态展开event节点
						function focus(param) {
							var data = param.data;
							var links = option.series[0].links;
							var nodes = option.series[0].nodes;
							if (
								data.source !== undefined
								&& data.target !== undefined
							) { //点击的是边
								var sourceNode = nodes.filter(function (n) {return n.name == data.source})[0];
								var targetNode = nodes.filter(function (n) {return n.name == data.target})[0];
								console.log("选中了边 " + sourceNode.name + ' -> ' + targetNode.name);
							} else { // 点击的是点
								console.log("选中了" + data.name );
								if(data.category) focusEntity(data.name);
							}
						}
						myChart1.on(ecConfig.EVENT.CLICK, focus);					
					});
				});
			},
			error: function(data){
				var output = '';
				for(var property in data){
					output += property + ':' + data[property] + ';';
				}
				console.log('[log] getTwitterEntities error information is:' + output);	
			}
		});
	};
	
    var getUser = function(entity){
		console.log("current focused entity is:" + entity +",get user url is:" + user_api_base + "/" + entity);
		$.getJSON(user_api_base + "/" + entity, function(users){
			if(entity == query){
				for(var i = 0; i < 5 && users.length >= 5; i++){
					twitteruser.push(users[i]["name"]);
				}
				console.log(" twitteruser is:" + twitteruser);
			}
			else{
				userdata = users[0];
				if(userdata["name"]){
					templateUser.when(TempoEvent.Types.RENDER_STARTING, function (event) {
						console.log("user begin");
						$("#user-list").show();
					}).when(TempoEvent.Types.RENDER_COMPLETE, function (event) {
						console.log("user done");
					}).render(userdata);
				}
			}
		});
	};
	
	// search tweets for the query
    var searchQueryTweets = function(query) {
        console.log("[log] search query: " + query);
        $("#loading-tweets").show();
        $.getJSON(tweet_api_base + "/" + query, function(tweets){
            tweetsdata = tweets.statuses;
	    if (tweetsdata == null || tweetsdata.length == 0) {
                $("#loading-tweets").hide();
	    	alert("no tweets found");
	    	return;
	    } else {
	    	console.log('start');
	    }
		
		newData = getTimeInterval(tweetsdata);
						
        next_results = tweets.search_metadata.next_results;
        templateTweet.when(TempoEvent.Types.RENDER_STARTING, function (event) {
                console.log('[log] loading tweets for:' + query);
                $("#loading-tweets").show();
            }).when(TempoEvent.Types.RENDER_COMPLETE, function (event) {
                $("#loading-tweets").hide();
                console.log('[log] loading tweets done');
				// $(".children").hide();
				$(".collapse-tweet").unbind().click(function(){
					var id = $(this).attr("index");
					if ($(this).hasClass("glyphicon-collapse-down")) {
						$(this).removeClass("glyphicon-collapse-down");
						$(this).addClass("glyphicon-collapse-up");
						$("#parent-" + id + " .children").show();
					} else {
						$(this).addClass("glyphicon-collapse-down");
						$(this).removeClass("glyphicon-collapse-up");
						$("#parent-" + id + " .children").hide();
					}
				});				
            }).render(newData);
			//auto load more result until clustering number up to 20
			//autoLoad(0, tweetsdata);
        });
    };
    
    // load more tweets
	var loadMore = function() {	
		console.log("[log] load more button clicked");
        // using api to get more data
        // update tweets data array
        $("#loading-tweets").show();
        $.getJSON(tweet_api_base + "/" + query + "/" + encodeURIComponent(next_results), function(tweets2){
            //console.log(tweet_api_base + "/" + query + "/" + encodeURIComponent(next_results));
            tweetsdata2 = tweets2.statuses;
            next_results = tweets2.search_metadata.next_results;
            for (var i = 0; i < tweetsdata2.length; i++) {
                tweetsdata.push(tweetsdata2[i]);
            }
			newData = getTimeInterval(tweetsdata);

            // re-generate the tweet list
            templateTweet.render(newData);
            $("#loading-tweets").hide();
        });	
	};
	
	$("#load-btn").click(loadMore);
	
	
	$("#search-btn").click(function(){
        console.log("[log] new query comes: " + query);
        query = $("#q").val();
        $("#query-name").text(query);
    });	
	
	getNewsEntities(query);
	getTwitterEntities(query);
	getEmbeddingEntities(query);
	searchQueryTweets(query);
    $("#query-name").text(query);
	
})

