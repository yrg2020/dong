# CXD2020

php v7.2 以上！

一个基于 lumen 的restful api库, 主要功能是将HTTP请求方法映射到资源的增删查改，将资源分类为Collection, Document, Action三种
约定默认使用JSON作为输入数据格式及输出表现层

```
Collection  集合型资源，其JSON表现为

{
  "collection": [                               // 资源集合
    {
      "id": 2,
      "name": "iphone6",
      "price": "20.12",
      "created_at": "2018-09-30 18:31:15",
      "updated_at": "2018-09-30 18:31:15"
    }
  ],
  "meta": {                                     // 元数据
    "href": "goods",                            // 链接
    "total": 64,                                // 集合总数
    "pagination": {                             // 分页信息
      "limit": 1,                               // 页面大小
      "currentPage": 1,                         // 当前页
      "totalPage": 64,                          // 总页数
      "haxNextPage": true                       // 是否有下一页
    }
  }
}
```

```
Document 文档型资源，其JSON表现为

{
  "document": {                                     // 文档内容
    "id": 2,
    "name": "iphone6",
    "price": "20.12",
    "created_at": "2018-09-30 18:31:15",
    "updated_at": "2018-09-30 18:31:15"
  },
  "meta": {                                         // 元数据
    "id": 2,                                        // 资源ID
    "etag": "456fefaa39d5e22c208de618861b51f6",     // etag 可通过HEAD查询etag在提交前判断当前资源是否已被更改
    "href": "goods",                                // 链接
    "links": [                                      // 关联资源链接
      {
        "rel": "comments",                          // 关联资源名称
        "href": "goods/2/comments"                  // 关联资源链接
      }
    ]
  }
}
```

```
Action 单一请求响应，其JSON表现为

{
    "data": {
        // any
    },
    "meta": null // optional
}

```


```
统一错误处理 http status >= 400 时，出现错误。 其JSON表现为 

{
    "status": 400, // http 状态码
    "code": 1001,  // 错误业务码
    "message": "你犯错了！", // 错误说明
    "stack": [] // optional, 在开发模式时，会有具体的错误调用堆栈。用来快速定位错误。
}

```


## 资源访问方式：

```
Collection 

url querystring中包含query, limit, page, sorts, withRels, relsQuery 6个查询参数 (均为可选参数)
其中 query, sort 接收一个JSON对象 {key:value}, withRels 接收一个JSON数组 ["rel"], limit和page均为 int

例:

GET /goods?query={"name":"iphone6"}&sorts={"id":"desc"}&page=1&limit=20&withRels=["comments"]&relsQuery={"comments":{"content":"xx"}}
意为： 在goods集合中查找"name"为"iphone6"且带有关联数据"comments",且"comments"的"content"等于"xx"的集合，并且按"id"倒序排列, 当前为第1页，页面大小为20   

支持的操作符：

 * { a:3, b:4 } 为： a = 3 and b = 4
 * { a:"%3%" } 为： a like '%3%'
 * { a: "[3,4]"} 为： a in (3, 4)
 * {a: "!3", b: "!4"} 为： a != 3 and b != 4
 * {a: ">3"} 为： a > 3
 * {a: "<3"} 为： a < 3
 * {a: ">=30<=50"} 为： a >= 30 and a <= 50
 

HEAD /goods 获取集合总数（X-TOTAL）

POST /goods 传递 [{key:value}] 时候为批量创建资源 传递 {key:value} 时为创建单个资源

PUT /goods  批量更新集合内资源（配合query, limit, page）

DELETE /goods 批量删除集合内资源（目前只支持query{id:[1,2,3]}批量传ID删除）

```

```
Document

url querystring仅含withRels 1个查询参数， 作用同上

例:

GET /goods/2?withRels=["comments"]  获取资源id为2且有关联数据"comments"的资源

HEAD /goods/2                       获取资源ETAG，用于提交前校验

PUT /goods/2                        更新资源，当此资源不存在时，则创建

PATCH /goods/2                      更新资源

DELTE /goods/2                      删除资源


```

关联数据

```


在集合和文档上都支持创建/更新文档时带上关联数据， 即：

PATCH /goods/2 （或 POST /goods）
{
    "name": "abc", 
    "comments": [
        {
            "id": 1,            // 更新时，关联文档ID必须带上
            "content": "修改了"
        }
    ]
}

有些时候，关联数据需要动态的新增、删除、更新， 使用PATCH无法达到需求，则使用PUT，意为Replace,如：

PUT /goods/2
{
   "name": "abc",
   "comments": [
        {
            "id": 1,
            "content": "#1更新评论"
        },
        {
            "content": "#2创建评论"
        },
        {
           "id": 333,
           "content": "#333指定id为333方式，如果333不存在则创建，否则更新"
        }
   ]
}

Goods#2 的Comments 只会为[#1,#2,#333]，其他的关联Comments会被删除
```

请求方法重载
```
在某些客户端下【例如微信小程序】不支持patch/put/delete等操作时，可使用"请求方法重载"来实现需求
具体方法有2种

1. 在请求头中加上 X-HTTP-Method-Override: METHOD

示例： 
POST /goods headers { "X-HTTP-Method-Override": "PATCH" }
            body [ {id:1, name:"xxx", id:2, name: "zzz"}]

2. 在请求数据体中加上 _method

示例：
POST /goods/1 body {_method: "PATCH", {name: "xxx"}}

可以看出，使用 X-HTTP-Method-Override 时，由于只是在请求头做添加项，不影响body内容。
        使用 _method 时在body中做添加项，会影响body内容 （强制请求数据类型为object,因此无法进行批量操作)
```

批量操作

```

POST /goods     // POST一个JSON数组时为批量创建
[
  {...}, {...}
]

PATCH /goods   // PATCH一个带id的数组时为批量更新
[
   {id:1, name: xxx},
   {id:2, name: xxx}
]

DELTE /goods?query={"ids":[1,2,3]} // 批量删除id为1，2，3的文档

// 也可使用X-HTTP-Method-Override/_method post一个大型id数组来做批量删除操作

POST /goods headers { "X-HTTP-Method-Override": "DELETE" }
body {
   id: [1,2,3,4,5,6,7,8,9,10,..,100]
}

POST /goods
body {
    _method: "DELTE",
    id: [1,2,3,4,5,6,7,8,9,10,..,100]
}


```


```
Action

同传统controller, 用于处理一些单一操作，例如（身份认证，上传文件等）
ps: 尽管类似于身份认证这种可以通过抽象为/tokens的方式走标准restul流程，但实际太繁琐，确实不如一个Action简单好用

```
## 请求处理流转

![image][design/flow.jpg]


## 推荐项目架构

![image][design/project.jpg]



## 部署：

服务器依赖软件安装参考dockfile

nginx/fpm/supervisor服务参考deploy文件夹下的内容
