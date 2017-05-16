//报销审批&报销详情
import React,{ Component,PropTypes} from 'react';
import { routerRedux } from 'dva/router';
import { connect } from 'dva';
import { Form, Icon, Button,Select, Row, Col,message, Steps,Popover} from 'antd';
import Main from '../../components/home/main';
import Pagetitle from '../../components/public/pagetitle';
import StepDetail from '../../components/details/stepDetail';
import BxDetail from '../../components/details/bxDetail';
import Accessory from '../../components/details/accessory';
import DetailImg from '../../components/details/detailimg';
import Approval from '../../components/details/approval';
import ConfirmButton from '../../components/details/confirmbutton';
import Confirm from '../../components/details/confirmPayment';
import cs from 'classnames';
import styles from '../style.less';
const Option = Select.Option;
const FormItem = Form.Item;

const ReimburseDetail = React.createClass({
    getInitialState(){
        return {
            ...this.props.Detail,
        };
    },
    handleConfirmClick(){
        const { Baoxiao_Detail } = this.props.Detail;
        this.props.dispatch({
            type:'Detail/PayMentConfirmQuery',
            payload:{
                isShowPaymentConfirm:true,
                apply_id:Baoxiao_Detail.apply_id
            }
        });
    },
    render(){
        const {isTitleStatus,Baoxiao_Detail,isShowPaymentConfirm,ApplyID} = this.props.Detail;
        let title=null,approval=null,confirm=null;
        if(isTitleStatus == null){
            title = '报销详情';
            approval = '';
        }else if(isTitleStatus == "approval"){
            title = '报销审批';
            approval = (<Approval ApplyID={ ApplyID } url="/reimburse" />);
        }else if(isTitleStatus == "confirm"){
            title = '报销确认';
            const GenTable = () => <ApplyTable tabledata={tabledata}/>;
            confirm = (<ConfirmButton handleClick={this.handleConfirmClick} />);
        }

        const GenConfirm = () => <Confirm handleClick={this.handleConfirmClick} isShowPaymentConfirm = { isShowPaymentConfirm }  details={Baoxiao_Detail}/>;

        let bxmx_columns = [{
                              title: '序号',
                              dataIndex: 'name',
                              key:'name',
                              render: (text, row, index) => index + 1,
                              width:60
                            },{
                              title: '报销金额',
                              key:'money',
                              dataIndex: 'money',
                              className:cs("t-r"),
                              width:120
                            },{
                              title: '报销类别',
                              key:'type_name',
                              dataIndex: 'type_name',
                              className:cs("t-c"),
                            },{
                              title: '费用明细',
                              key:'des',
                              dataIndex: 'des',
                            }];
        const formItemLayout = {
                              labelCol: {
                                xs: { span: 24 },
                                sm: { span: 3 },
                                md: { span: 2 },
                              },
                              wrapperCol: {
                                xs: { span: 24 },
                                sm: { span: 14 },
                              },
                            };

            let fj_columns = [{
                              title: '序号',
                              dataIndex: 'xh',
                              key:'xh',
                              render: (text, row, index) => index + 1,
                              width:60
                            },{
                              title: '附件名',
                              key:'name',
                              dataIndex: 'name',
                              className:cs("t-r"),
                              width:120
                            },{
                              title: '格式',
                              key:'ext',
                              dataIndex: 'ext',
                              className:cs("t-c"),
                            },{
                              title: '操作',
                              key:'option',
                              dataIndex: 'option',
                              render: (text, record, index) => {
                                return (
                                  <a href="#">删除</a>
                                );
                              }
                            }];

            let name = '',bank_name='',bank_id='',bank_des='';
            if(Object.keys(Baoxiao_Detail).length > 0){
                name = Baoxiao_Detail.person
                bank_id = Baoxiao_Detail.info.bank_card_id;
                bank_name = Baoxiao_Detail.info.bank_name;
                bank_des = Baoxiao_Detail.info.bank_des;
            }


        return(
            <Main location={location}>
                <Row>
                    <div className={styles.home_wrap}>
                        <Pagetitle title={title} />
                        <StepDetail stepdata={Baoxiao_Detail} />
                        <BxDetail columns={bxmx_columns} dataSource={Baoxiao_Detail.info} label="报销明细" />
                        <FormItem {...formItemLayout}  label="报销卡号">
                            <p style={{marginTop:5}}>{ name }&nbsp;&nbsp;&nbsp;{ bank_name }</p>
                            <p>{ bank_id }</p>
                            <p>{ bank_des }</p>
                        </FormItem>
                        <Accessory columns={fj_columns} dataSource={Baoxiao_Detail.info} />
                        <DetailImg imgdata={Baoxiao_Detail.pics} />
                        { approval }
                        { confirm }
                        <GenConfirm />
                    </div>
                </Row>
            </Main>
        );
    }

});

ReimburseDetail.propTypes = {
   location: PropTypes.object,
   Detail: PropTypes.object,
   dispatch: PropTypes.func,
};

function mapStateToProps({ Detail }) {
  return { Detail };
}

export default connect(mapStateToProps)(Form.create()(ReimburseDetail));
