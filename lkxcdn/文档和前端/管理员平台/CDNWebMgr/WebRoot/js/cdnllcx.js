// JavaScript Document
$(function(){
	var user =$("#user").val();
	var type=$("#type").val();
	var id=$("#id").val();
	var url="CDNywcx!tjini";
	var date = new Date();
	$("#startDate").val(date.format("yyyy-MM-dd")); 
    $("#endDate").val(date.format("yyyy-MM-dd")); 
    $.post(url,{ "user":user,"type":type,"id":id },function(data){
			var arr = data.split("***");
		  var zone = eval("("+arr[1]+")");
		  var channel = eval("("+arr[0]+")");
		  
		  for( count = 0; count < zone.length; count++ ){
		  	$("#regionSimpleCode").append('<option value="'+zone[count]+'">'+zone[count]+'</option>');
		  }
		  
		  for( count = 0; count < channel.length; count++ ){
		  	$("#channel").append('<option value="'+channel[count]+'">'+channel[count]+'</option>');
		  }
		  
		  $("select[id='channel']").multiselect(
		 		{ minWidth:197,
		 		  noneSelectedText:lang.multiselect.channel.noneSelectedText,
		 		  selectedText:lang.multiselect.channel.selectedText,
		 		  close:function(){changeTip();},
		 		  optionWidth:370}
		 	).multiselectfilter();

			$("select[id='regionSimpleCode']").multiselect(
		 		{ minWidth:127,
		 		  noneSelectedText:lang.multiselect.region.noneSelectedText,
		 		  selectedText:lang.multiselect.region.selectedText,
		 		  optionWidth:270}
		 	).multiselectfilter();
		 	   		 	
})

})

function query(){
	             
	             var user =$("#user").val();
	             var type=$("#type").val();
	             var id=$("#id").val();
	             var url="CDNywcx!tjcx";
			     var     settype = "_flow";
				 var    time_str = "";
				 var    zone_str = "";
				 var channel_str = "";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
				 var   startDate = new Date(start);
				 var     endDate = new Date(end);
				 
				 var mmSec = (endDate.getTime() - startDate.getTime());  
      	 var getOffDays =  mmSec / 3600000 / 24; 
			   if( getOffDays > 31 ){ alert('查询时间段不能超过31天'); return; } 
				 
				 var loading  = new ol.loading({id:"flowAllPieChart_container"});
				 var loading1 = new ol.loading({id:"flowIspPieChart_container"});
				 var loading2 = new ol.loading({id:"flowColumnChart_container"});
				 loading_show( loading, loading1, loading2 );
				
				 time_str = "[\"" + start + "\",\"" + end +"\"]";
				 
				 zone = document.getElementById("regionSimpleCode");  
			   var zone_str = "[";  
			   for( i = 0; i < zone.length; i++ ){     
			        if( zone.options[i].selected ){  
			            zone_str += "\"" + zone.options[i].value + "\",";  
			        }  
			    }
			    zone_str = zone_str.substr(0,zone_str.length-1) + "]";   
				 
		var	   channel = document.getElementById("channel");  
			   var channel_str = "[";  
			   for( i = 0; i < channel.length; i++ ){     
			        if(channel.options[i].selected){  
			            channel_str += "\"" + channel.options[i].value + "\",";  
			        }  
			    }
			    channel_str = channel_str.substr(0,channel_str.length-1) + "]";
			    
			    if( zone_str.length <= 2 || channel_str.length <= 2 ){ 
			    	alert('请先选择完整的查询条件'); 
			    	loading.hide(); 
			    	loading1.hide(); 
			    	loading2.hide();
			    	 
			    	return; 
			    }
			    
			    $.post(url,{"user":user,"type":type,"id":id,"time":time_str,"channel":channel_str,"zone":zone_str},function(data){
			    		   var arr = data.split("***");
			    		_flow_type = eval("("+arr[0]+")");
			    		      _isp = eval('('+arr[1]+')');
			        _flow_date = eval('('+arr[2]+')');
			    _province_flow = eval('('+arr[3]+')');
			    		
			    		add_row(_flow_date);
	
			    		add_province_flow_row(_province_flow);
			    		createChart(_flow_type,'流量类型','flowAllPieChart_container');
			    		createChart(_isp,'各ISP比例','flowIspPieChart_container');
			    		createSerialChart(_flow_date,'','flowColumnChart_container');

			    		loading_hide( loading, loading1, loading2 );
			    })
			}
function add_row(_arr){
	$("#en_flow_all_table").empty();

	$("#en_flow_all_table").append('<tr><th style=\"text-align:center;\" class=\"index\">日期</th><th width="230px" style=\"text-align:center;\">流量（单位MB）</th><th width="150px" style=\"text-align:center;\">带宽峰值（单位Mbps）</th><th width="150px" style=\"text-align:center;\">峰值时间</th></tr>');
	for( count = 1; count <= _arr.length; count++ ){
		$("#en_flow_all_table").append('<tr><td style=\"text-align:center;\" class=\"index\">'+_arr[count-1]["day"]+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["flow"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["bandwidth"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["datetime"]+'</td></tr>');
	}
}
			
function add_province_flow_row(_arr){
	$("#province_flow").empty();

	$("#province_flow").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">省份名称</th><th width="150px" style=\"text-align:center;\">流量比例(%) </th></tr>');
	for( count = 1; count <= 10 && count <= _arr.length; count++ ){
		$("#province_flow").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["province"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["per"]+'</td></tr>');
	}
}
function createChart(chartData,chartTitle,chartDiv) {
					 // PIE CHART
			    chart = new AmCharts.AmPieChart();
			
			    // title of the chart
			//    chart.addTitle(chartTitle, 16);
			
			    chart.dataProvider = chartData;
			    chart.titleField = "type";
			    chart.valueField = "value";
			    chart.sequencedAnimation = true;
			    chart.startEffect = "elastic";
			    chart.innerRadius = "30%";
			    chart.startDuration = 2;
			    chart.labelRadius = 35;
			
			    // the following two lines makes the chart 3D
			    chart.depth3D = 10;
			    chart.angle = 15;
			
			    // WRITE                                 
			    chart.write(chartDiv);
			}
			
			function createSerialChart(chartData,chartTitle,chartDiv) {
			    // SERIAL CHART
			    chart = new AmCharts.AmSerialChart();
			    chart.autoMarginOffset = 0;
			    chart.dataProvider = chartData;
			    chart.categoryField = "day";
			    chart.startDuration = 1;
			
			    // AXES
			    // category
			    var categoryAxis = chart.categoryAxis;
			    categoryAxis.labelRotation = 45; // this line makes category values to be rotated
			    categoryAxis.gridAlpha = 0;
			    categoryAxis.fillAlpha = 1;
			    categoryAxis.fillColor = "#FAFAFA";
			    categoryAxis.gridPosition = "start";
			
			    // value
			    var valueAxis = new AmCharts.ValueAxis();
			    valueAxis.dashLength = 5;
			    valueAxis.title = chartTitle;
			    valueAxis.axisAlpha = 0;
			    chart.addValueAxis(valueAxis);
			
			    // GRAPH
			    var graph = new AmCharts.AmGraph();
			    graph.valueField = "flow";
			    graph.colorField = "color";
			    graph.balloonText = "[[category]]: [[value]]";
			    graph.type = "column";
			    graph.lineAlpha = 0;
			    graph.fillAlphas = 1;
			    chart.addGraph(graph);
			
			    // WRITE
			    chart.write(chartDiv);
			}
			
			function loading_show(loading, loading1, loading2){
				  loading.show();
				  loading1.show();
				  loading2.show();
			}
			
			function loading_hide( loading, loading1, loading2 ){
				  loading.hide();
				  loading1.hide();
				  loading2.hide();
			}
	  
Date.prototype.format = function(format)
{
 var o = {
 "M+" : this.getMonth()+1, //month
 "d+" : this.getDate(),    //day
 "h+" : this.getHours(),   //hour
 "m+" : this.getMinutes(), //minute
 "s+" : this.getSeconds(), //second
 "q+" : Math.floor((this.getMonth()+3)/3),  //quarter
 "S" : this.getMilliseconds() //millisecond
 }
 if(/(y+)/.test(format)) format=format.replace(RegExp.$1,
 (this.getFullYear()+"").substr(4 - RegExp.$1.length));
 for(var k in o)if(new RegExp("("+ k +")").test(format))
 format = format.replace(RegExp.$1,
 RegExp.$1.length==1 ? o[k] :
 ("00"+ o[k]).substr((""+ o[k]).length));
 return format;
}