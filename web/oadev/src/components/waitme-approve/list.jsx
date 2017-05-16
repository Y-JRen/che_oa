import React, { PropTypes } from 'react';
import { connect } from 'dva'
import { Table, Popconfirm, Pagination, Modal, Button,Form, Row, Col, Input, Icon, Menu, Dropdown, DatePicker, Select } from 'antd';

const WaitmeList = React.createClass({
    // 筛选事件
    handleChange(pagination, filters, sorter) {

        //console.log(filters);
        //console.log(sorter);
        const { at,type,onSorting }=this.props.waitme;
        let sorting = "";
        let filterType = null;

        if (filters.type_value.length > 0) {
            filterType  = filters.type_value[0];
        }
        if (sorter.order != undefined) {
          sorting = sorter.order != 'descend' ? 1:0;
          //console.log(sorting);
        }
        this.props.onSorting(sorting, filterType);
    },
    render(){

        const { dataSource,keywords,start_time,end_time,at,type,current,repayment,loading,total,sortingType} = this.props.waitme;
            const columns = [{
                title: '序号',
                dataIndex: 'id',
                key: 'id',
                        render:(text, row, index)=>(
                                        index+1
                                    ),
            },{
                title: '申请时间',
                dataIndex: 'date',
                key: 'date',
                render:(text, record, index)=>{
                    return record.date;
                },
                sorter: (a, b) => a.date - b.date,
                sortOrder:sortingType == "date" ? sorting : "",
            },{
                title: '审批单编号',
                dataIndex: 'apply_id',
                key: 'apply_id'
            },{
                title:'类型',
                dataIndex:'type_value',
                key:'type_value',
                filters:[
                    {text:'报销', value:'1'},
                    {text:'借款', value:'2'},
                    {text:'还款', value:'3'},
                ],
                filteredValue: repayment,
            },{
                title:'标题',
                dataIndex:'title',
                key:'ttitle'
            },{
                title:'发起人',
                dataIndex:'person',
                key:'person'
            },{
                title:'审批人',
                dataIndex:'approval_persons',
                key:'approval_persons'

            },{
                title:'抄送人',
                dataIndex:'copy_person',
                key:'copy_person'
            },{
                title:'状态',
                dataIndex:'next_des',
                key:'next_des',
            },{
                title:'操作',
                dataIndex:'operation',
                render:(text,record)=>{
                    let url=null;
                    switch(record.type_value){
                        case '报销':
                            url = "#/reimbursedetail?apply_id="+record.apply_id+"&type=approval";
                        break;
                        case '借款':
                            url = "#/loanmentdetail?apply_id="+record.apply_id+"&type=approval";
                        break;
                        case '还款':
                            url = "#/repaymentdetail?apply_id="+record.apply_id+"&type=approval";
                        break;
                    }
                    return (<p><a href={url}>审批</a></p>);
                }
            }]
            const pagination = {
                total,
                current,
                pageSize: 20,
                onChange: ()=>{},
            };

            return (
                <div>
                  <Table
                    columns={columns}
                    loading={loading}
                    dataSource={dataSource}
                    rowKey={record => record.id}
                    onChange={this.handleChange}
                    pagination={false}
                    size="middle"
                    bordered
                  />
                <Pagination
                    showQuickJumper
                    defaultCurrent={current} total={total} defaultPageSize={20} />
                </div>
            );

        }
    });

WaitmeList.propTypes = {
  onPageChange: PropTypes.func,
  dataSource: PropTypes.array,
  loading: PropTypes.any,
  total: PropTypes.any,
  current: PropTypes.any,
};

function mapStateToProps({waitme}){
    return { waitme }
}
export default connect(mapStateToProps)(WaitmeList);
