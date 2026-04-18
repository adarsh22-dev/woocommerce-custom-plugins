
jQuery(function($){
let chart;

function renderChart(data){
 const ctx=document.getElementById('wvChart');
 if(!ctx) return;
 if(chart) chart.destroy();

 chart=new Chart(ctx,{
  type:'bar',
  data:{labels:Object.keys(data),datasets:[{data:Object.values(data),backgroundColor:'#6f6bdc',maxBarThickness:60}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
 });
}

renderChart(window.wvChartData||{});

$('.save').on('click',function(){
 let r=$(this).closest('tr');
 let badge=r.find('.wv-badge');
 let btn=$(this);

 btn.prop('disabled',true).text('Saving...');
 badge.attr('class','wv-badge pending').text('Saving');

 $.post(WV.ajax,{
  action:'wv_save',
  nonce:WV.nonce,
  vid:r.data('vid'),
  sku:r.find('.sku').val(),
  price:r.find('.price').val(),
  stock:r.find('.stock').val()
 },function(res){
  btn.prop('disabled',false).text('Save');

  if(!res.success){
    badge.attr('class','wv-badge error').text('Error');
    wcfm_notification(res.data,'error');
    return;
  }

  badge.attr('class','wv-badge success').text('Saved');
  wcfm_notification(res.data.message,'success');

  $('#wvStock').text('₹'+res.data.cards.stock_value);
  $('#wvVar').text(res.data.cards.variations);
  $('#wvLow').text(res.data.cards.low);
  $('#wvOut').text(res.data.cards.out);

  renderChart(res.data.chart);
 });
});
});
