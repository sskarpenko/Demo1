$(document).ready(function () {
    // сохраняем массив id грида при загрузке страницы
    var $gridKeys = [];

    $(".grid-view tbody tr").each(function () {
        $gridKeys.push($(this).attr('data-key'));
    });

    function getPrevGridId($id) {
        var currIndexId = $gridKeys.indexOf($id.toString());
        if (currIndexId > -1) {
            var prevId = $gridKeys[currIndexId - 1];

            if (typeof prevId !== 'undefined')
                return prevId;
        }
        return false;
    }

    function getNextGridId($id) {
        var currIndexId = $gridKeys.indexOf($id.toString());
        if (currIndexId > -1) {
            var nextId = $gridKeys[currIndexId + 1];

            if (typeof nextId !== 'undefined')
                return nextId;
        }
        return false;
    }

    function setPrevButtonUrl() {
        var params, currentId, value, prevButton, prevId;

        prevButton = $('#prev-btn');
        value = prevButton.attr('value');
        if ((prevButton.length > 0) && (value.indexOf('?id=') == -1)) {
            params = JSON.parse(prevButton.attr('data-params'));
            currentId = params['id'];
            prevId = getPrevGridId(currentId);
            value = value + '?id=' + prevId;
            prevButton.attr('value', value);
            prevButton.attr('disabled', !prevId);
        }
    }

    function setNextButtonUrl() {
        var params, currentId, value, nextButton, nextId;

        nextButton = $('#next-btn');
        value = nextButton.attr('value');
        if ((nextButton.length > 0) && (value.indexOf('?id=') == -1)) {
            params = JSON.parse(nextButton.attr('data-params'));
            currentId = params['id'];
            nextId = getNextGridId(currentId);
            value = value + '?id=' + nextId;
            nextButton.attr('value', value);
            nextButton.attr('disabled', !nextId);
        }
    }

    $(document).on('click', '#prev-btn', function (e) {
        setTimeout(function () {
            setPrevButtonUrl();
            setNextButtonUrl();
        }, 1000);
    });

    $(document).on('click', '#next-btn', function (e) {
        setTimeout(function () {
            setPrevButtonUrl();
            setNextButtonUrl();
        }, 1000);
    });

    // открытие модального окна при нажатии на кнопку с классом modalButton
    $(document).on('click', '.showModalButton', function (e) {
        e.preventDefault();

        var button = $(this);
        var buttonId = button.attr('id');

        var url = button.attr('value');
        var title = button.attr('name');

        var modal = new Modal.CustomModal('#modal', url, title);
        modal.show();

        $('#modal').on('shown.bs.modal', function () {
            setTimeout(function () {
                setPrevButtonUrl();
                setNextButtonUrl();
            }, 1000);
        });
    });

    // закрытие модального окна при нажатии на кнопку с классом hideModalButton
    $(document).on('click', '.hideModalButton', function (e) {
        e.preventDefault();

        $('#modal').modal('hide');
    });

    // Делаем неактивными кнопки пока не придёт ответ с сервера.
    // Необходимо чтоб нельзя было создать несколько записей при плохой связи
    $('#modal').on('beforeSubmit', function (event) {
        $(this).find('[type=submit]').attr('disabled', true).addClass('disabled');
        $(this).find('[type=button]').attr('disabled', true).addClass('disabled');
    });
});

var Modal = (function (exports) {
    'use strict';

    var CustomModal = (function (elementId, url, title) {
        this.elementId = elementId;
        this.url = url;
        this.title = title;
    });

    CustomModal.prototype.show = function () {
        var elementId = this.elementId;
        var elementName = elementId.slice(1);
        var container = $(elementId + 'Content');
        var header = $(elementId + 'Header');
        var modal = $(elementId);
        // Очищаем контейнер
        container.html('Пожалуйста, подождите. Идет загрузка...');
        // Выводим модальное окно, загружаем данные
        modal.data('bs.modal')._config.backdrop = 'static';
        modal.data('bs.modal')._config.keyboard = false;
        modal.find(header).text(this.title);
        document.getElementById(elementName + 'Header').innerHTML = '<h4>' + this.title + '</h4>';
        modal.modal('show').find(container).load(this.url, function (response, status, xhr) {
            var msg = 'Извините, но произошла ошибка';
            if (status == "error") {
                modal.find(header).text(msg);
                container.html(xhr.status + " " + xhr.statusText);
            }
        });
        modal.on('shown.bs.modal', function (modal, id) {
            $(id).find('input,select,textarea').each(function () {
                var bsModal = $(this);
                if ((bsModal.attr('type') != 'hidden') || (bsModal.is('select'))) {
                    // skip readonly and disabled inputs
                    if (bsModal[0].hasAttribute('readonly') || bsModal.is(':disabled')) return true; // continue
                    // process select 2 focus
                    if (bsModal.data('select2')) {
                        bsModal.select2('focus');
                        return false;
                    }
                    modal.focus();
                    return false;
                }
            });
            //$(this).off('shown.bs.modal'); // disable to prevent trigger event multiple times
        });

        // при закрытии показываем предыдущее модальное окно, если передан параметр prev
        modal.on('hidden.bs.modal', function () {
            $(elementName + 'Content').html('');
        });
    };

    CustomModal.prototype.hide = function () {
        $(this.id).modal('hide');
    };

    CustomModal.prototype.openDomainModal = function () {

    };

    exports.CustomModal = CustomModal;

    return exports;
}({}));
