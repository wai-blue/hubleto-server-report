<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Api;

use Hubleto\App\Community\Documents\Generator;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Services\DefaultData;

/**
 * Generates the PDF report of a server with Documents\Generator (the same principle as the invoices).
 */
class GenerateReport extends \Hubleto\Erp\Controllers\ApiController
{
  public function renderJson(): array
  {
    $idServer = $this->router()->urlParamAsInteger('idServer');

    try {
      /** @var Server $mServer */
      $mServer = $this->getModel(Server::class);
      $t = $mServer->table;

      // reading through prepareReadQuery() checks that the current user may see the server
      $server = $mServer->record->prepareReadQuery()->where($t . '.id', $idServer)->first();
      if (!$server) return ['status' => 'error', 'message' => $this->translate('Server was not found or you have no access to it.')];

      /** @var DefaultData $defaultData */
      $defaultData = $this->getService(DefaultData::class);
      $defaultData->ensure();

      $idTemplate = (int) $server->id_template;
      if ($idTemplate <= 0) $idTemplate = $defaultData->getReportTemplateId();
      if ($idTemplate <= 0) return ['status' => 'error', 'message' => $this->translate('Template of the server report was not found.')];

      /** @var Generator $generator */
      $generator = $this->getService(Generator::class);

      $documentName = $this->translate('Server report') . ' ' . $server->name;
      $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $server->name)), '-');
      $file = 'server-report-' . ($slug === '' ? 'server' : $slug) . '-' . date('Ymd-His') . '.pdf';

      $vars = $generator->getPreviewVars(Server::class, $idServer);
      $idDocument = $generator->generatePdfDocumentFromTemplate($documentName, Server::class, $idServer, $idTemplate, $file, $vars, true);

      if ($idDocument > 0) {
        $mServer->record->where($t . '.id', $idServer)->update([
          'id_document' => $idDocument,
          'id_template' => $idTemplate,
          'pdf' => $file,
        ]);
      }
    } catch (\Throwable $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }

    return ['status' => 'success', 'idDocument' => $idDocument, 'file' => $file];
  }
}
