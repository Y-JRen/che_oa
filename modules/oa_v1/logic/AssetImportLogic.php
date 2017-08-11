<?php
/**
 * Created by PhpStorm.
 * User: xiongjun
 * Date: 2017/8/11
 * Time: 13:25
 */

namespace app\modules\oa_v1\logic;

use app\logic\Logic;
use app\models\Asset;
use app\models\AssetBrand;
use app\models\AssetGetList;
use app\models\AssetList;
use app\models\AssetListLog;
use app\models\AssetType;
use app\models\Person;
use moonland\phpexcel\Excel;

/**
 * 资产导入逻辑
 * Class AssetImportLogic
 * @package app\modules\oa_v1\logic
 */
class AssetImportLogic extends Logic
{
    /**
     * 状态
     * @var array
     */
    public $status = [
        '未使用' => 1,
        '空闲中' => 1,
        '使用' => 2,
        '使用中' => 2,
        '已报废' => 3,
        '报废' => 3,
        '已丢失' => 4,
    ];
    
    /**
     * 资产类型
     *
     * @param $name
     * @param int $pid
     *
     * @return AssetType|array|null|\yii\db\ActiveRecord
     */
    public function getAssetType($name, $pid = 0)
    {
        $assetType = AssetType::find()->where(['name' => $name])->one();
        if(empty($assetType)) {
            $assetType = new AssetType();
            $assetType->name = $name;
            $assetType->parent_id = $pid;
            $assetType->has_child = 0;
            $assetType->save();
        }
        return $assetType;
    }
    
    /**
     * 获取品牌
     *
     * @param $name
     *
     * @return AssetBrand|array|null|\yii\db\ActiveRecord
     */
    public function getAssetBrand($name)
    {
        $assetBrand = AssetBrand::find()->where(['name' => $name])->one();
        if(empty($assetBrand)) {
            $assetBrand = new AssetBrand();
            $assetBrand->name = $name;
            $assetBrand->save();
        }
        return $assetBrand;
    }
    
    /**
     * 增加系统日志
     *
     * @param $assetListId
     * @param $type
     * @param null $des
     * @param int $personId
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function addAssetListLog($assetListId, $type, $des = null, $personId = 0)
    {
        $log = new AssetListLog();
        $log->person_id = $personId;
        $log->asset_list_id = $assetListId;
        $log->type = $type;
        $log->des = $des;
        
        $log->created_at = time();
        if ($log->save()) {
            return true;
        }
        throw new \yii\base\Exception('日志保存失败');
    }
    
    /**
     * 增加领取信息
     *
     * @param $assetListId
     * @param $assetId
     * @param $personId
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function addAssetGetList($assetListId, $assetId, $personId)
    {
        $assetGetList = new AssetGetList();
        $assetGetList->apply_id = '0';
        $assetGetList->person_id = $personId;
        $assetGetList->asset_id = $assetId;
        $assetGetList->asset_list_id = $assetListId;
        $assetGetList->status = 2;
        $assetGetList->created_at = time();
        if ($assetGetList->save()) {
            return true;
        }
        throw new \yii\base\Exception(BaseLogic::instance()->getFirstError($assetGetList->errors));
    }
    
    public function import($file)
    {
        $data = Excel::import($file, [
            'setFirstRecordAsKeys' => false,
            'setIndexSheetByName' => true,
        ]);
        array_shift($data);
        foreach ($data as $k => $v) {
            if(!$v["A"] || !$v["B"] || !$v["C"] || !$v["D"]) {
                continue;
            }
            $assetTypeA = $this->getAssetType($v["A"]);
            $assetTypeB = $this->getAssetType($v["B"], $assetTypeA->id);
            $assetBrand = $this->getAssetBrand($v["C"]);
            $asset = Asset::find()->where(['name' => $v["D"]])->one();
            if(empty($asset)) {
                $asset = new Asset();
                $asset->asset_brand_id = $assetBrand->id;
                $asset->asset_brand_name = $v["C"];
                $asset->asset_type_id = $assetTypeB->id;
                $asset->asset_type_name = $v["A"] . '-'.$v["B"];
                $asset->name = $v["D"];
                $asset->price = 0;
                $asset->save();
            }
            if($this->status[trim($v['G'])] == 2) {
                /**
                 * @var Person $person
                 */
                $person = Person::find()->where([
                    'person_name' => $v['H'],
                    'phone' => $v['L'],
                ])->one();
                if($person) {
                    $personId = $person->person_id;
                    $des = $person->person_name.'使用中';
                } else {
                    $person = Person::find()->where([
                        'person_name' => $v['H'],
                    ])->one();
                    if($person) {
                        $personId = $person->person_id;
                        $des = $person->person_name.'使用中';
                    } else {
                        $personId = 0;
                        $des = '未知-使用中';
                    }
                }
            } else {
                $personId = 0;
            }
            $assetList = new AssetList();
            $assetList->asset_id = $asset->id;
            $assetList->asset_number = $v['E'] ? : $v['F'];
            $assetList->stock_number = $v['F'];
            $assetList->sn_number = $v['K'] ? : '';
            $assetList->price = $v['M'] ? : 0;
            $assetList->status = $this->status[trim($v['G'])];
            $assetList->created_at = time();
            $assetList->person_id = $personId;
            $assetList->save();
            $asset->price += trim($v['M']);
            $asset->amount += 1;
            $type = 2;
            if($this->status[trim($v['G'])] == 1) {
                $asset->free_amount += 1;
                $des = '首次入库';
                $type = 1;
            }
            if($this->status[trim($v['G'])] == 3){
                $des = '报废';
                $type = 3;
            }
            if($this->status[trim($v['G'])] == 4){
                $des = '丢失';
                $type = 4;
            }
            $desc = isset($v['N']) ?  trim($v['N']) : $des;
            $asset->save();
            $this->addAssetListLog($assetList->id, $type, $desc, $personId);
            if($personId && $assetList){
                $this->addAssetGetList($assetList->id, $asset->id, $personId);
            }
            echo '第'.$k . '条' .PHP_EOL;
        }
    }
}