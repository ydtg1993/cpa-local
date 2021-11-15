$(function () {

    // 监听textarea长度
    // $(".sub_text>textarea").bind('input',function () {
    //     var txtNum=$(this).val().length;
    //     $(this).next("span").text(txtNum)
    // })

    // 导航菜单的显示隐藏
    var falg = true;
    $('.menuIcon').click(function () {
        $('.modal-money').css({ 'right':' -100%' });
        if(falg){
            $('.menu').css({'left': 0});
            $('.menulist').css({'left': 0});
            stop()
            falg = false
        }else{
            allow()
            $('.menu').css({'left': '100%'});
            $('.menulist').css({'left':' -150%'});
            falg = true
        }
    });
    $('.menu-other').click(function () {
        $('.menu').css({'left': '100%'});
        $('.menulist').css({'left':' -150%'});
        falg = true
    });


    // 等级提示的显示关闭
    // $('.know-level').click(function () {
    //     $('.modal-know-level').fadeIn()
    // });
    $('.icon-fork').click(function () {
        $('.modal-know-level').fadeOut()
    });

    // 提款提示框的显示隐藏
    var money = true;
    $('.takeMoney').click(function () {
        $('.menu').css({'left': '100%'});
        $('.menulist').css({'left':' -150%'});
        if(money){
            modalMoney(false,0)
        }else{
            modalMoney(true,'-100%')
        }
    });
    $('.moneyZindex').click(function () {
        modalMoney(true,' -100%')
    })
});
function modalMoney(ay,num) {
    $('.modal-money').css({'right':num});
    money = ay;
}

//可以滚动
function allow(){
    $("boby").css({"height":"auto", "overflow-y":"scroll","overflow-x":"hidden"});
    $("html").css({ "height":"auto", "overflow-y":"scroll"})
}
//禁止底层滚动
function stop(){
    $("boby").css({"height":"100%", "overflow":"hidden"});
    $("html").css({"height":"100%", "overflow":"hidden"})
}