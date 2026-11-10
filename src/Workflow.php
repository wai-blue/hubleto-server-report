<?php

namespace Hubleto\App\External\WaiBlue\ServerReport;

class Workflow extends \Hubleto\App\Community\Workflow\Workflow
{

  /**
   * Loads servers of a workflow (cards of the kanban view).
   *
   * @param int $idWorkflow
   * @param array $filters
   *
   * @return array
   *
   */
  public function loadItems(int $idWorkflow, array $filters): array
  {
    $fOwner = (int) ($filters['fOwner'] ?? 0);

    /** @var Models\Server $mServer */
    $mServer = $this->getModel(Models\Server::class);
    $items = $mServer->record->prepareReadQuery()
      ->where($mServer->table . '.id_workflow', $idWorkflow)
      ->where($mServer->table . '.is_closed', 0)
    ;

    if ($fOwner > 0) {
      $items = $items->where($mServer->table . '.id_owner', $fOwner);
    }

    $items = $items->get()?->toArray() ?? [];

    foreach ($items as $key => $item) {
      $items[$key]['_DETAIL_URL'] = 'server-report/' . $item['id'];
      $items[$key]['_DETAIL_VIEW'] = '@Hubleto:App:External:WaiBlue:ServerReport/WorkflowItemDetail.twig';
    }

    return $items;
  }

}
