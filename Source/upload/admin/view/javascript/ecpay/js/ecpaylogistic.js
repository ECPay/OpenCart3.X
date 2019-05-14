// 建立物流訂單
function ecpay_create_shipping(url){

    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json',
        success: function (sMsg){
            console.log(sMsg)
            
            alert(sMsg.msg)
            location.reload();
        },
        beforeSend:function(){
             $.blockUI({ 
                message: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>',
                showOverlay: true, 
                centerY: true,
                css: {
                    width: '250px', 
                    left:   '50%', 
                    border: 'none',
                    padding: '15px',
                    backgroundColor: '#000',
                    '-webkit-border-radius': '10px',
                    '-moz-border-radius': '10px',
                    opacity: .6,
                    color: '#fff'
                },
                overlayCSS:  { 
                    backgroundColor:'#fff', 
                    opacity:0, 
                    cursor:'wait' 
                },      
            });
        },
        error: function (sMsg1, sMsg2){
            setTimeout($.unblockUI, 10)
            //alert('error_submitForm');
        }
    });
}

// 變更門市
function ecpay_express_map(url){
    location.href=url;
}