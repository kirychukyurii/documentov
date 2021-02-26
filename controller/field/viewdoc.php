<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		http://documentov.com/license
 * @link		http://www.documentov.com
 */
class ControllerFieldViewdoc extends Controller
{

  public function index()
  {
    if (
      !empty($this->request->get['document_uid']) && !empty($this->request->get['field_uid'])
      && !empty($this->request->get['history_id'])
    ) {
      $json = [];
      $this->load->model('document/document');
      $this->load->model('doctype/doctype');
      $field_uid = $this->db->escape($this->request->get['field_uid']);
      $document_uid = $this->db->escape($this->request->get['document_uid']);
      $field_info = $this->model_doctype_doctype->getField($field_uid);
      if (!$field_info || $field_info['type'] !== "viewdoc") {
        return;
      }
      // получаем документ из поля, от клиента его не берем - ненадежно
      $view_document_uid = $this->model_document_document->getFieldValue($field_uid, $document_uid, FALSE, TRUE);
      if (!$view_document_uid) {
        return;
      }
      // проверяем доступ к документу
      $view_document_info = $this->model_document_document->getDocument($view_document_uid);
      if (!$view_document_info) {
        return;
      }
      // все проверки завершены, получаем шаблон
      $history_info = $this->model_document_document->getDocumentHistory($this->request->get['document_uid'], $this->request->get['history_id']);
      $data_render = [
        'document_uid' => $view_document_uid,
        'doctype_uid' => $view_document_info['doctype_uid'],
        'conditions' => "",
        'mode' => 'view',
        'values' => json_decode($history_info['version'] ?? "", TRUE)
      ];
      if (!empty($field_info['params']['templates'])) {
        foreach ($field_info['params']['templates'] as $template) {
          if ($template['doctype_uid'] == $view_document_info['doctype_uid']) {
            $data_render['template'] = htmlspecialchars_decode($template['template']);
            break;
          }
        }
      }
      if (empty($data_render['template'])) {
        // предопределенного шаблона нет, отдаем в дефолтном
        $template = $this->model_document_document->getTemplate($view_document_uid, "view");
        $data_render['template'] = htmlspecialchars_decode($template['template']);
        $data_render['conditions'] = $template['conditions'];
      }
      $json['form'] = $this->load->controller('document/document/renderTemplate', $data_render);
      $this->response->addHeader('Content-type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }
}
