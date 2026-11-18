<?php

namespace app\common\service\chat;

use Throwable;
use think\facade\Db;
use app\common\service\ChatBot;
use app\common\service\LiuService;
use app\common\model\ChatConversation;

/**
 * 聊天流程编排：取会话、落库用户消息、生成回复、落库机器人回复
 */
class ChatService
{
    protected ConversationService $conversationService;
    protected MessageService $messageService;
    protected OrderService $orderService;
    protected ReportService $reportService;
    protected TimeLimitService $timeLimitService;
    protected RiskService $riskService;

    public function __construct()
    {
        $this->conversationService = new ConversationService();
        $this->messageService      = new MessageService();
        $this->orderService        = new OrderService();
        $this->reportService       = new ReportService($this->orderService);
        $this->timeLimitService    = new TimeLimitService();
        $this->riskService         = new RiskService();
    }

    /**
     * 当天聊天记录，无记录时写入欢迎语
     */
    public function history(int $userId): array
    {
        $conversation = $this->conversationService->getOrCreate($userId);
        // 管理员操作消息属于后台留痕，会员端不展示
        $messages     = $this->messageService->listByConversation((int)$conversation->id, 50, false);

        if (!$messages) {
            $welcome = $this->messageService->create($conversation, 'bot', ChatBot::welcome());
            $this->conversationService->touch($conversation, $welcome);
            $messages = [$welcome];
        }

        return array_map([$this->messageService, 'format'], $messages);
    }

    /**
     * 发送消息并返回用户消息与机器人回复
     */
    public function send(int $userId, string $content): array
    {
        $conversation = $this->conversationService->getOrCreate($userId);

        Db::startTrans();
        try {
            $userMessage = $this->messageService->create($conversation, 'user', $content);
            $botMessage  = $this->messageService->create($conversation, 'bot', $this->buildReply($conversation, $content));
            $this->conversationService->touch($conversation, $botMessage);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [
            $this->messageService->format($userMessage),
            $this->messageService->format($botMessage),
        ];
    }

    /**
     * 清空当天会话的聊天记录，仅保留欢迎语
     */
    public function clear(int $userId): array
    {
        $conversation = $this->conversationService->getOrCreate($userId);

        Db::startTrans();
        try {
            $this->messageService->clear((int)$conversation->id);
            $welcome = $this->messageService->create($conversation, 'bot', ChatBot::welcome());
            $this->conversationService->touch($conversation, $welcome);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [$this->messageService->format($welcome)];
    }

    /**
     * 生成机器人回复
     * 报表指令优先；已风控或限制时段只回复不可操作；其余内容解析下单
     */
    protected function buildReply(ChatConversation $conversation, string $content): string
    {
        if ($this->timeLimitService->isReportCommand($content)) {
            return $this->reportService->todayReport($conversation, $this->conversationService->settings($conversation));
        }

        if ($this->riskService->isRisked($conversation)) {
            return $this->timeLimitService->restrictedMessage();
        }

        if ($this->timeLimitService->isRestricted()) {
            return $this->timeLimitService->restrictedMessage();
        }

        $parsed = LiuService::parseOrderCodes($content);
        $this->orderService->record($conversation, $parsed);
        return ChatBot::reply($parsed);
    }
}
