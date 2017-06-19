<?php
/**
 * Created by PhpStorm.
 * User: xiongjun
 * Date: 2017/6/13
 * Time: 15:22
 */

namespace app\modules\oa_v1\logic;


use app\logic\Logic;
use app\models\Apply;
use app\models\ApplyBuyList;
use app\models\ApplyDemandList;
use app\models\ApprovalLog;



/**
 * 申请基础类
 *
 * Class BaseApplyLogic
 * @package app\modules\oa_v1\controllers
 */
class BaseApplyLogic extends Logic
{
    /**
     * 审批流程
     *
     * @param Apply $apply
     * @return array
     */
    public function getFlowData($apply)
    {
        $data = [];
        $approvalLog = ApprovalLog::find()->where(['apply_id' => $apply->apply_id])->all();
        $data[] = [
            "title" => "发起申请",
            "name" => $apply->person,
            "date"=> date('Y-m-d H:i', $apply->create_time),
            "org" => PersonLogic::instance()->getOrgNameByPersonId($apply->person_id),
            "status" => 2
        ];
        
        if(!empty($approvalLog)) {
            $count = count($approvalLog);
            foreach ($approvalLog as $k => $v){
                $status = $diff_time = 0;
                $title = $v->approval_person.'审批';
                if($v->is_to_me_now == 1 && $v->result == 0) {
                    $status = 1;
                    $diff_time = time() - $apply->create_time;
                    $title .= '中';
                }
                if($v->result == 1) {
                    $status = 2;
                    $diff_time = $v->approval_time - $apply->create_time;
                }
                if($v->result == 2) {
                    $status = 3;
                    $diff_time = $v->approval_time - $apply->create_time;
                    $title .= '不通过';
                }
                
                $data[] = [
                    "title" => $title,
                    "name" => $v->approval_person,
                    "date"=> $v->approval_time ? date('Y-m-d H:i', $v->approval_time) : '',
                    "org" => PersonLogic::instance()->getOrgNameByPersonId($v->approval_person_id),
                    "status" => $status,
                    'diff_time' => $diff_time
                ];
                if ($count == $k + 1 && $apply->status == 99 && $apply->cai_wu_need == 1) {
                    $data[] = [
                        "title" => "完成",
                        "name" => '',
                        "date"=> date('Y-m-d H:i', $v->approval_time),
                        "org" => '',
                        "status" => 2,
                        'diff_time' => $diff_time
                    ];
                }
            }
        }
        if($apply->cai_wu_need == 2 && $apply->status == 4) {
            $data[] = [
                "title" => "财务确认",
                "name" => '',
                "date"=> '',
                "org" => '',
                'diff_time' => time() - $apply->create_time,
                "status" => 1
            ];
        }
    
        if($apply->cai_wu_need == 2 && $apply->status < 4 ) {
            $data[] = [
                "title" => "财务确认",
                "name" => '',
                "date"=> '',
                "org" => '',
                "status" => 0
            ];
        }
    
        if($apply->status == 99) {
            if ($apply->cai_wu_need == 2) {
                $caiWuFuKuan = Apply::findOne($apply->apply_id);
                $data[] = [
                    "title" => "财务确认",
                    "name" => $caiWuFuKuan->person,
                    "date"=> date('Y-m-d H:i', $caiWuFuKuan->create_time),
                    "org" => PersonLogic::instance()->getOrgNameByPersonId($caiWuFuKuan->person_id),
                    "status" => 2,
                    'diff_time' => $caiWuFuKuan->create_time - $apply->create_time
                ];
                $data[] = [
                    "title" => "完成",
                    "name" => '',
                    "date"=> date('Y-m-d H:i', $caiWuFuKuan->create_time),
                    "org" => '',
                    "status" => 2,
                    'diff_time' => $caiWuFuKuan->create_time - $apply->create_time
                ];
            }
            
        }  else {
            $data[] = [
                "title" => "完成",
                "name" => '',
                "date"=> '',
                "org" => '',
                "status" => 0
            ];
        }
        
        return $data;
    }
    
    /**
     * 请购单列表
     *
     * @param $applyId
     * @return array
     */
    public function getApplyBuyList($applyId)
    {
        $data = [];
        $assetLogic = AssetLogic::instance();
        $list = ApplyBuyList::find()->where(['apply_id' => $applyId])->all();
        foreach ($list as $v) {
            $data[] = [
                'asset_type_id' => $assetLogic->getAssetType($v->asset_type_id),
                'asset_brand_id' => $assetLogic->getAssetBrand($v->asset_brand_id),
                'name' => $v->name,
                'price' => $v->price,
                'amount' => $v->amount,
            ];
        }
        return $data;
    }
    
    /**
     * 需求单 需求明细
     * @param $applyId
     * @return array
     */
    public function getApplyDemandList($applyId)
    {
        $data = [];
        $list = ApplyDemandList::find()->where(['apply_id' => $applyId])->all();
        foreach ($list as $v) {
            $data[] = [
                'name' => $v->name,
                'amount' => $v->amount,
            ];
        }
        return $data;
    }
    
    /**
     * 获取请购基础信息
     * @param $apply
     * @return array
     */
    public function getBaseApply($apply)
    {
        return [
            "apply_id" => $apply->apply_id,
            "create_time" => date('Y-m-d H:i', $apply->create_time),
            "next_des" => $apply->next_des,
            "title" => $apply->title,
            "type" => $apply->type,
            "person" => $apply->person,
            'date' => date('Y年m月d日', $apply->create_time),
            'copy_person' => $apply->copy_person,
            'approval_persons' => $apply->approval_persons,
        	'pdf' => $apply->apply_list_pdf
        ];
    }
}