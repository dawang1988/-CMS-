<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use app\service\LogService as StructuredLog;

class ScoreService
{
    public static function add(int $userId, int $score, string $reason = '', string $tenantId = '88888888'): bool
    {
        if ($score <= 0) {
            StructuredLog::warning('积分增加失败', [
                'user_id' => $userId,
                'score' => $score,
                'reason' => '积分值必须大于0',
            ]);
            return false;
        }

        Db::startTrans();
        try {
            $user = Db::name('user')->where('id', $userId)->lock(true)->find();
            if (!$user) {
                StructuredLog::warning('积分增加失败', [
                    'user_id' => $userId,
                    'score' => $score,
                    'reason' => '用户不存在',
                ]);
                Db::rollback();
                return false;
            }

            $beforeScore = $user['score'] ?? 0;
            $afterScore = $beforeScore + $score;

            Db::name('user')->where('id', $userId)->update(['score' => $afterScore]);

            Db::name('score_log')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => 1,
                'score' => $score,
                'before_score' => $beforeScore,
                'after_score' => $afterScore,
                'reason' => $reason,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            Db::commit();
            StructuredLog::info('积分增加成功', [
                'user_id' => $userId,
                'score' => $score,
                'before_score' => $beforeScore,
                'after_score' => $afterScore,
                'reason' => $reason,
            ]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('积分增加异常', [
                'user_id' => $userId,
                'score' => $score,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function deduct(int $userId, int $score, string $reason = '', string $tenantId = '88888888'): bool
    {
        if ($score <= 0) {
            StructuredLog::warning('积分扣减失败', [
                'user_id' => $userId,
                'score' => $score,
                'reason' => '积分值必须大于0',
            ]);
            return false;
        }

        Db::startTrans();
        try {
            $user = Db::name('user')->where('id', $userId)->lock(true)->find();
            if (!$user || ($user['score'] ?? 0) < $score) {
                StructuredLog::warning('积分扣减失败', [
                    'user_id' => $userId,
                    'score' => $score,
                    'reason' => $user ? '积分不足' : '用户不存在',
                    'current_score' => $user['score'] ?? 0,
                ]);
                Db::rollback();
                return false;
            }

            $beforeScore = $user['score'];
            $afterScore = $beforeScore - $score;

            Db::name('user')->where('id', $userId)->update(['score' => $afterScore]);

            Db::name('score_log')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => 2,
                'score' => $score,
                'before_score' => $beforeScore,
                'after_score' => $afterScore,
                'reason' => $reason,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            Db::commit();
            StructuredLog::info('积分扣减成功', [
                'user_id' => $userId,
                'score' => $score,
                'before_score' => $beforeScore,
                'after_score' => $afterScore,
                'reason' => $reason,
            ]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('积分扣减异常', [
                'user_id' => $userId,
                'score' => $score,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
