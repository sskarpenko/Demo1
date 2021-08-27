/**
 * Created by SS on 23.08.2019.
 */

$(document).ready(function() {

    var mqttTmp;
    var reconnectTimeout = 5;
    var host="mqtt.cntr.one";
    var port=9001;
    var mqttName;
    var mqttPass;
    var user_uuid;
    var out_msg;
    var iconPath;

    // if (document.location.pathname != '/site/login') {
        $.ajax({
            url: '/site/mqtt-data',
            method: 'GET',
            success:function(data){
                mqttName = data['mqttUser'];
                mqttPass = data['mqttPass'];
                user_uuid = data['user_uuid'];
                if (user_uuid) MQTTconnect();
            }
        });
    // }

    function sendNotification(title, options) {
        if (!("Notification" in window)) {
            alert('Ваш браузер не поддерживает HTML Notifications, его необходимо обновить.');
        }
        else if (Notification.permission === "granted") {
            var notification = new Notification(title, options);
            function clickFunc() {
                alert('Пользователь кликнул на уведомление');
            }
            notification.onclick = clickFunc;
        }
        else if (Notification.permission !== 'denied') {
            Notification.requestPermission(function (permission) {
                if (permission === "granted") {
                    var notification = new Notification(title, options);

                } else {
                    alert('Вы запретили показывать уведомления'); // Юзер отклонил наш запрос на показ уведомлений
                }
            });
        } else {
        }
    }

    function onConnect() {
        console.log("Connected ");
        mqttTmp.subscribe("user/"+user_uuid+"/orders");
        // console.log("channel: user/"+user_uuid+"/orders");
    }

    function onFailure(message) {
        console.log("Failed");
        setTimeout(MQTTconnect, reconnectTimeout);
    }

    function onMessageArrived(r_message){
        out_msg = JSON.parse(r_message.payloadString);
        // console.log(out_msg);
        sendNotification('Уведомление по распоряжению № '+out_msg.orders_reg_num+' от '+out_msg.str_orders_created_at, {
            body: out_msg.message,
            dir: 'auto',
            icon: document.location.protocol+'//'+document.location.host+'/ic_notifications.png'
        });
    }

    function MQTTconnect() {
        console.log("connecting to "+ host +" "+ port);

        var x=Math.floor(Math.random() * 1000);
        var cname="ordersform-"+x;
        mqttTmp = new Paho.MQTT.Client(host,port,cname);
        var options = {
            useSSL:true,
            timeout: 5,
            userName: mqttName,
            password: mqttPass,
            onSuccess: onConnect,
            onFailure: onFailure
        };
        mqttTmp.onMessageArrived = onMessageArrived;
        mqttTmp.connect(options); //connect
    }

    $(document).on('click', '.modalButton', function (e) {
        e.preventDefault();

        var container = $('#modalContent');
        var header = $('#modalHeader');
        // Очищаем контейнер
        container.html('Пожалуйста, подождите. Идет загрузка...');
        // Выводим модальное окно, загружаем данные
        $("#modal").data('bs.modal')._config.backdrop = 'static';
        $("#modal").data('bs.modal')._config.keyboard = false;
        $('#modal').find(header).text($(this).attr('title'));
        $('#modal').modal('show').find(container).load($(this).attr('value'));
        $('#modal').on('shown.bs.modal', function () {
            var idArr = [];
            $('#modal').find('input,select').each(function() {
                if (this.id!=''){
                    idArr[idArr.length]= this.id;
                }
            });
            // console.log(idArr);
            $("#"+idArr[0]).focus();
            idArr = [];
        });
        $("#modal").on('hidden.bs.modal', function () {
            $('#modalContent').html('');
        });
    });

    function dropDownFixPosition(button, dropdown) {
        var dropDownTop = button.offset().top + button.outerHeight();
        var left = button.offset().left - dropdown.width() + button.parent().width();
        dropdown.css('top', dropDownTop + "px");
        dropdown.css('left', left + "px");
        dropdown.css('position', "absolute");

        // dropdown.css('width', dropdown.width());
        // dropdown.css('heigt', dropdown.height());
        dropdown.css('display', 'block');
        dropdown.appendTo('body');
    }

    function returnDropdownToParent(button, dropdown) {
        dropdown.hide();
        dropdown.insertAfter(button);
    }

    var openedUl = null;
    // $('.table-responsive').on('show.bs.dropdown', function (e) {
    $(document).on('show.bs.dropdown', '.table-responsive', function (e) {
        var buttonGroup = e.relatedTarget;
        var ul = $(buttonGroup).siblings('ul');
        openedUl = ul;
        dropDownFixPosition($(buttonGroup), $(ul));
    });

    // $('.table-responsive').on('hide.bs.dropdown', function (e) {
    $(document).on('hide.bs.dropdown', '.table-responsive', function (e) {
        returnDropdownToParent($(e.relatedTarget), openedUl);
    });

    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $.ajax({
                url: '/site/class-menu'
            });
            $('#sidebar').toggleClass('active');
        });
    });

});
