<?php

namespace app\api\controller;

use ba\Tree;
use Throwable;
use think\facade\Db;
use think\facade\Config;
use app\common\controller\Frontend;
use app\common\library\token\TokenExpirationException;
use app\common\service\LiuService;

class Index extends Frontend
{
    protected array $noNeedLogin = ['index'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 前台和会员中心的初始化请求
     * @throws Throwable
     */
    public function index(): void
    {
        // 注意：原 index() 的站点/菜单返回数据已被测试代码替换，恢复时参考 git 中的原始版本
        $data = [];

        // ==================== 号码下单解析测试用例 ====================
        // 默认从请求参数读取，正式使用保持这一行即可
        $code = $this->request->param('code/s', '');

        // 手动测试：取消下面任意一行注释即可覆盖上面的取值，一次只放开一行
        // 注释含义：count = 去重后号码数，quantity = 累计金额总和（均为实测值）
        // 返回的 code 字段为本次实际解析的原文，warnings 为未能识别的片段

        // ---------- 一、数字号码：分隔符与数量词 ----------
        // $code = '4.6.8各50';                                              // count 3   quantity 150
        // $code = '4.6.8.10.16.24.34.36.40.42.44.46.1.9.11.15.21.23.35.37.39.47.49各50。05.17.29.41.26.14.20.32各30。11.08.23.20.32.35.44.47各150'; // count 31 quantity 2590
        // $code = '4、6、8、各50';                                          // count 3   quantity 150  顿号
        // $code = '4，6，8 各50';                                           // count 3   quantity 150  中文逗号
        // $code = '4 6 8 各 50 05 17 各 30';                                // count 5   quantity 210  纯空格分段
        // $code = '4.6.8=50 5.17 下30';                                     // count 5   quantity 210  等号与「下」
        // $code = '4, 6, 8 每个数 50；5 号各 30';                           // count 4   quantity 180  「每个数」「号各」
        // $code = '０４.６.８各５０';                                        // count 3   quantity 150  全角数字

        // ---------- 二、数字号码：连字符的各种变体 ----------
        // $code = '7-17-27-37-47-4-14-24-34-44各5';                         // count 10  quantity 50   ASCII 连字符
        // $code = '7－17－27－37－47各5';                                    // count 5   quantity 25   全角连字符
        // $code = '7—17—27各5';                                             // count 3   quantity 15   长破折号
        // $code = '7~17~27各5';                                             // count 3   quantity 15   波浪号
        // $code = '13-34-15各20';                                           // count 3   quantity 60

        // ---------- 三、无效字符按分隔符处理（岁、税） ----------
        // $code = '6.10.11.1岁各25。4.8.12岁各50';                          // count 7   quantity 250   裸 岁 不选号，仅作分隔符
        // $code = '6.10.11.1税各25。4.8.12税各50';                          // count 7   quantity 250

        // ---------- 四、文字选号：生肖（Liuconfig::$zodiacMap） ----------
        // $code = '马猴鼠狗猪各15。虎蛇各5。';                              // count 29  quantity 355
        // $code = '马 猴 鼠 各15';                                          // count 13  quantity 195  空格分隔
        // $code = '龙鼠兔各10。猪猴各50。';                                 // count 20  quantity 520

        // ---------- 五、文字选号：五行（Liuconfig::$wuxingMap） ----------
        // $code = '金木水火土各10';                                         // count 49  quantity 490  覆盖全部号码
        // $code = '金木各10';                                               // count 20  quantity 200

        // ---------- 六、文字选号：波色（Liuconfig::$boMap） ----------
        // 兰、篮 均等同 蓝；波色单双按号码本身单偶切分（红单 8 个、红双 9 个）
        // $code = '红各10';                                                  // count 17  quantity 170
        // $code = '蓝各50';                                                  // count 16  quantity 800
        // $code = '绿各10';                                                  // count 16  quantity 160
        // $code = '红蓝绿各10';                                              // count 49  quantity 490  三色合计全部号码
        // $code = '红单各10';                                                // count 8   quantity 80
        // $code = '红双各10';                                                // count 9   quantity 90
        // $code = '蓝单各30';                                                // count 8   quantity 240
        // $code = '蓝双各20';                                                // count 8   quantity 160
        // $code = '绿单各10';                                                // count 9   quantity 90
        // $code = '绿双各10';                                                // count 7   quantity 70
        // $code = '兰单各30';                                                // count 8   quantity 240   兰 => 蓝
        // $code = '篮单各30';                                                // count 8   quantity 240   篮 => 蓝
        // $code = '兰各20';                                                  // count 16  quantity 320   兰 => 蓝
        // $code = '兰各20。篮各30';                                          // count 16  quantity 800   兰/篮 同为蓝波，金额累加

        // ---------- 七、文字选号：合数单双（Liuconfig::$heshuDanShuang，各位数字之和的奇偶） ----------
        // $code = '单各30。双各25';                                         // count 49  quantity 1350

        // ---------- 八、混合与边界 ----------
        // $code = '兰双各50。马狗猪各10。兔各15。04.29各10';                // count 24  quantity 610   文字与数字混排
        // $code = '狗猴牛虎兔蛇羊各10。牛狗各15。鼠各5。兰单各30。';        // count 36  quantity 660   多段连发
        // $code = '7-17-27-37-47-4-14-24-34-44各5。1.3.9.11岁各10。27.39.各5。03各10。猴各15。猪鼠各5。'; // count 23 quantity 210
        // $code = '马各10。鼠各20';                                         // count 9   quantity 130   不同文字组独立计算
        // $code = '兔各15。04.29各10';                                      // count 5   quantity 80    04 与兔的 4 累加为 25

        // ---------- 九、金额单位：元|块|米|闷|斤|刀|¥|井（可带可不带，也兼容写在数字前） ----------
        // $code = '4.6.8各50元';                                             // count 3   quantity 150
        // $code = '4.6.8各50块';                                             // count 3   quantity 150
        // $code = '4.6.8各50米';                                             // count 3   quantity 150
        // $code = '4.6.8各50闷';                                             // count 3   quantity 150
        // $code = '4.6.8各50斤';                                             // count 3   quantity 150
        // $code = '4.6.8各50刀';                                             // count 3   quantity 150
        // $code = '4.6.8各50¥';                                              // count 3   quantity 150
        // $code = '4.6.8各50井';                                             // count 3   quantity 150
        // $code = '4.6.8各¥50';                                              // count 3   quantity 150   单位写在数字前
        // $code = '4.6.8 各 50 元';                                          // count 3   quantity 150   单位与数字之间可有空格
        // $code = '4.6.8各50元 5.17各30块';                                  // count 5   quantity 210   多段带不同单位
        // $code = '4.6.8各50元。5.17各30斤';                                 // count 5   quantity 210
        // $code = '4.6.8各50元5.17各30';                                     // count 5   quantity 210   单位后可无分隔符直接接下一段
        // $code = '门7岁7各10元';                                            // count 4   quantity 40    与岁数选号混用
        // $code = '马猴各15刀';                                              // count 9   quantity 135   与文字选号混用

        // ---------- 十、岁数选号：门N / 门N岁（必须带「门」，Liuconfig::$numToZodiac => $zodiacMap） ----------
        // 岁数 N 的生肖取自 $numToZodiac[N]，号码取该生肖在 $zodiacMap 中的全部号码：
        // 7岁 => 鼠(7,19,31,43)  1岁 => 马(1,13,25,37,49)  12岁 => 羊(12,24,36,48)
        // $code = '门7岁7各10';                                              // count 4   quantity 40    尾部 7 与鼠的 7 重复，组内去重
        // $code = '门7各10';                                                 // count 4   quantity 40
        // $code = '门7岁各10';                                               // count 4   quantity 40
        // $code = '门1岁各10';                                               // count 5   quantity 50    马
        // $code = '门12各10。门7各20';                                       // count 8   quantity 120   羊 + 鼠
        // $code = '门7岁7各10。门7各10';                                     // count 4   quantity 80    四个号码各累加到 20
        // $code = '门60各10';                                                // 号码 60 超范围被剔除，见第十二组

        // ---------- 十一、不带「门」不选号（用于观察行为与 warnings） ----------
        // 裸 7岁 中的 岁 仅作分隔符，不会展开生肖号码
        // $code = '7岁各10';                                                 // count 1   quantity 10    仅识别数字 7
        // $code = '7岁平持200';                                             // count 0   warnings 提示未匹配片段，且保持原文

        // ---------- 十二、号码范围校验：号码必须在 1-49 之间 ----------
        // 超范围的号码单独剔除（同组其他号码照常下单），并写入 warnings
        // $code = '门60各10';                                                // count 0   quantity 0     warnings 号码超出范围(1-49): 60
        // $code = '4.60.8各10';                                             // count 2   quantity 20    60 剔除，4、8 各 10
        // $code = '4.99.8各10';                                             // count 2   quantity 20    99 剔除
        // $code = '4.50各10';                                               // count 1   quantity 10    50 剔除
        // $code = '4.100.8各10';                                            // count 2   quantity 20    100 剔除
        // $code = '4.0.8各10';                                              // count 2   quantity 20    0 剔除（有效范围 1-49）
        // $code = '4.6.8各50。60.70各10';                                   // count 3   quantity 150   整段全是超范围号码，该段不下单
        // $code = '4.49各10';                                               // count 2   quantity 20    49 为合法上界

        // ---------- 十三、结算格式：每个号码一行「生肖号码=下单/实际 【结算金额】」 ----------
        // 分两步，不迭代：
        //   第一步 外发金额全为 0，用用户下单金额算出结算金额，据此得到外发金额 waifa
        //   第二步 实际下单金额 = 用户下单金额 - waifa；参数A = 全部 waifa 之和
        //          结算金额 = 实际下单金额之和 × 系数 - 实际下单金额 × 倍数 - 参数A × 系数
        // 配置见 config/liu.php：coefficient=0.96、multiple=47、risk_amount=1000
        // 没有外发时行首只显示下单金额；有外发时显示「下单金额/实际下单金额」
        // $code = '4各50。6各100。8各200';                                   // 参数A 266：兔4=50/28 【-1490.72】 牛6=100/28 【-1490.72】 猪8=200/28 【-1490.72】
        // $code = '4.6.8各50';                                               // 参数A 78：兔4=50/24 【-1133.76】 牛6=50/24 【-1133.76】 猪8=50/24 【-1133.76】
        // $code = '13各385';                                                 // 参数A 356：马13=385/29 【-1676.92】
        // $code = '门7岁7各10';                                              // 参数A 0：鼠7=10 【-431.6】 鼠19=10 【-431.6】 鼠31=10 【-431.6】 鼠43=10 【-431.6】

        // ---------- 十四、风控参数 waifa（config/liu.php 的 risk_amount，默认 1000） ----------
        // 仅亏损且亏损额超过风控金额时计算：waifa =（亏损额 - 风控金额）/ 倍数，向上取整；未超过为 0
        // waifa、actual（实际下单金额）只出现在返回 JSON 的 settled.items 里
        // $code = '4各50。6各100。8各200';                                   // 兔4 waifa=22（1014/47）  牛6 waifa=72（3364/47）  猪8 waifa=172（8064/47）
        // $code = '4.6.8各50';                                               // 亏损 2206，waifa=26（1206/47），实际下单 24
        // $code = '13各385';                                                 // 亏损 17725.4，waifa=356（16725.4/47），实际下单 29
        // $code = '门7岁7各10';                                              // 亏损 431.6 未超 1000，waifa=0，实际下单 10
        // $code = '4各21';                                                   // 亏损 966.84 未超 1000，waifa=0，结算 【-966.84】
        // $code = '4各22';                                                   // 亏损 1012.88 刚超 1000，waifa=1（12.88/47 向上取整），结算 兔4=22/21 【-967.8】


        if ($code !== '') {
            $data['parsed']  = LiuService::parseOrderCodes($code);
            $data['settled'] = LiuService::settleOrderCodes($data['parsed']['list']);
        }
        $data['code'] = $code;

        $this->success('', $data);
    }
}
