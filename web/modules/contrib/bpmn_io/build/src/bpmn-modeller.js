import BpmnModeler from 'bpmn-js/lib/Modeler';
import {
    BpmnPropertiesPanelModule,
    BpmnPropertiesProviderModule,
    CamundaPlatformPropertiesProviderModule,
} from 'bpmn-js-properties-panel';
import {
  ElementTemplatesPropertiesProviderModule,
} from 'bpmn-js-element-templates';
import CamundaBpmnModdle from 'camunda-bpmn-moddle/resources/camunda.json'
import ElementTemplateChooserModule from '@bpmn-io/element-template-chooser';

window.modeller = new BpmnModeler({
    container: '#bpmn-io .canvas',
    propertiesPanel: {
        parent: '#bpmn-io .property-panel'
    },
    additionalModules: [
        BpmnPropertiesPanelModule,
        BpmnPropertiesProviderModule,
        CamundaPlatformPropertiesProviderModule,
        ElementTemplatesPropertiesProviderModule,
        ElementTemplateChooserModule,
    ],
    moddleExtensions: {
        camunda: CamundaBpmnModdle
    },
    elementTemplates: [],
  });
