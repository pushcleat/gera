(function ($, Drupal, drupalSettings) {

    Drupal.bpmn_io = {};

    Drupal.behaviors.bpmn_io = {
        attach: function (context, settings) {
            if (Drupal.bpmn_io.modeller === undefined) {
                window.addEventListener("resize", function (event) {
                    let container = $('#bpmn-io');
                    let offset = container.offset();
                    let width = container.width();
                    $('#bpmn-io .canvas')
                        .css('top', offset.top)
                        .css('left', offset.left)
                        .css('width', width);
                    $('#bpmn-io .property-panel')
                        .css('max-height', $(window).height() - offset.top);
                }, false);
                window.dispatchEvent(new Event("resize"));

                Drupal.bpmn_io.modeller = window.modeller;
                Drupal.bpmn_io.loader = Drupal.bpmn_io.modeller.get('elementTemplatesLoader');
                Drupal.bpmn_io.loader.setTemplates(settings.bpmn_io.templates);
                Drupal.bpmn_io.open(settings.bpmn_io.bpmn, !settings.bpmn_io.isnew);
                $('#bpmn-io input.button.eca-save').click(Drupal.bpmn_io.export);
                $('#bpmn-io input.button.eca-close').click(function () {
                    window.location = drupalSettings.bpmn_io.collection_url;
                });
                Drupal.bpmn_io.dragAndDrop($("#bpmn-io .property-panel")[0]);
            }
            Drupal.bpmn_io.prepareMessages();
        }
    };

    Drupal.bpmn_io.prepareMessages = function () {
        $('.messages-list:not(.bpmn-io-processed)')
            .addClass('bpmn-io-processed')
            .click(function () {
                $(this).empty();
            });
    }

    Drupal.bpmn_io.export = async function () {
        $('.messages-list').empty();
        let result = await Drupal.bpmn_io.modeller.saveXML({format: true});
        let request = Drupal.ajax({
            url: drupalSettings.bpmn_io.save_url,
            submit: result.xml,
            progress: {
                type: 'fullscreen',
                message: Drupal.t('Saving model...'),
            },
        });
        request.execute();
        Drupal.bpmn_io.prepareMessages();
    }

    Drupal.bpmn_io.open = async function (bpmnXML, readOnlyId) {
        await Drupal.bpmn_io.modeller.importXML(bpmnXML);
        Drupal.bpmn_io.canvas = Drupal.bpmn_io.modeller.get('canvas');
        Drupal.bpmn_io.overlays = Drupal.bpmn_io.modeller.get('overlays');
        Drupal.bpmn_io.canvas.zoom('fit-viewport');
        if (readOnlyId) {
            let idField = $('#bio-properties-panel-id');
            let modelId = $(idField)[0].value;
            let eventBus = Drupal.bpmn_io.modeller.get('eventBus');
            eventBus.on('element.click', function (e) {
                if (e.element.id === modelId) {
                    $(idField)
                        .hide()
                        .parent('.bio-properties-panel-textfield').find('label span').show();
                }
                else {
                    $(idField)
                        .show()
                        .parent('.bio-properties-panel-textfield').find('label span').hide();
                }
            });
            $(idField)
                .hide()
                .parent('.bio-properties-panel-textfield').find('label').append('<span>: ' + modelId + '</span>');
        }
    }

    Drupal.bpmn_io.dragAndDrop = function (panel) {
        const BORDER_SIZE = 4;
        let m_pos;
        function resize(e) {
            const dx = m_pos - e.x;
            m_pos = e.x;
            panel.style.width = (parseInt($(panel).outerWidth()) - BORDER_SIZE + dx) + "px";
        }
        panel.addEventListener("mousedown", function (e) {
            if (e.offsetX < BORDER_SIZE) {
                m_pos = e.x;
                document.addEventListener("mousemove", resize, false);
            }
        }, false);
        document.addEventListener("mouseup", function () {
            document.removeEventListener("mousemove", resize, false);
        }, false);
    }

})(jQuery, Drupal, drupalSettings);
