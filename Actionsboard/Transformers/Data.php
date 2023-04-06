<?php

namespace Modules\Actionsboard\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class Data extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @group _ Module Actionsboard
     * @param  \Illuminate\Http\Request
     * @return array
     */
     public function toArray($request)
     {


         $data = $this->data;

         $data['id'] = $this->id;
         $data['created_at'] = $this->created_at->__toString();
         $data['updated_at'] = $this->updated_at->__toString();

         if (isset($data['startDate']) && !isset($data['endDate'])) {
            $data['endDate'] = $data['startDate'];
         }

         return $data;

         // $data = [
         //     'id' => isset($this->id) ? $this->id : null,
         //     'created_at' => isset($this->created_at) ? $this->created_at->__toString() : null,
         //     'updated_at' => isset($this->updated_at) ? $this->updated_at->__toString() : null,
         // ];

         // $model = '\Modules\Actionsboard\Entities\\'.$request->entity;
         // $newEntity = new $model;
         // $fields = $newEntity->getFieldsKeys();

         // if (isset($this->data) && is_array($this->data)) {
         //     foreach ($fields as $fieldKey) {
         //         if (isset($this->data[$fieldKey])) {
         //           $value = $this->data[$fieldKey];
         //           if (in_array($fieldKey, $newEntity->dateFields)) {
         //             if ($value != null && strtotime($value)) {
         //               $value = (new Carbon($value))->format('d M, Y');
         //             } else {
         //               $value = null;
         //             }
         //           }
         //           $data[$fieldKey] = $value;
         //         } else {
         //           $data[$fieldKey] = null;
         //         }
         //     }
         // }

         // if (isset($data['startDate']) && !isset($data['endDate'])) {
         //    $data['endDate'] = $data['startDate'];
         // }

         // return $data;
     }


}
