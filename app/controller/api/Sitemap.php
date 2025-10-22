<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Config as ConfigModel;
use app\service\EnhancedSitemapGenerator;
use think\Request;

/**
 * Sitemap生成控制器（统一使用 EnhancedSitemapGenerator）
 */
class Sitemap extends BaseController
{
    private $generator;

    public function __construct()
    {
        // 获取系统配置的前端网站URL
        $siteUrl = ConfigModel::getConfig('site_url', '');

        if (empty($siteUrl)) {
            $siteUrl = request()->domain();
        }

        // 初始化增强sitemap生成器
        $this->generator = new EnhancedSitemapGenerator($siteUrl);
    }

    /**
     * 生成TXT格式sitemap
     */
    public function generateTxt()
    {
        try {
            $result = $this->generator->generateTxtSitemap();

            if ($result['success']) {
                return Response::success([
                    'file' => '/sitemap.txt',
                    'url' => $result['url'],
                    'count' => $result['count']
                ], 'TXT格式sitemap生成成功');
            } else {
                return Response::error($result['message'] ?? '生成失败');
            }
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成XML格式sitemap
     */
    public function generateXml()
    {
        try {
            // 使用增强生成器生成主sitemap
            $result = $this->generator->generateMainSitemap();

            if ($result['success']) {
                return Response::success([
                    'file' => '/sitemap.xml',
                    'url' => $this->generator->getBaseUrl() . '/sitemap.xml',
                    'count' => $result['count']
                ], 'XML格式sitemap生成成功');
            } else {
                return Response::error($result['message'] ?? '生成失败');
            }
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成HTML格式sitemap
     */
    public function generateHtml()
    {
        try {
            // 使用增强生成器生成HTML sitemap
            $result = $this->generator->generateHtmlSitemap();

            if ($result['success']) {
                return Response::success([
                    'file' => '/sitemap.html',
                    'url' => $result['url']
                ], 'HTML格式sitemap生成成功');
            } else {
                return Response::error($result['message'] ?? '生成失败');
            }
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成所有格式的sitemap
     */
    public function generateAll()
    {
        try {
            $results = [];

            // 生成TXT
            $txtResult = $this->generateTxt();
            $results['txt'] = $txtResult->getData();

            // 生成XML
            $xmlResult = $this->generateXml();
            $results['xml'] = $xmlResult->getData();

            // 生成HTML
            $htmlResult = $this->generateHtml();
            $results['html'] = $htmlResult->getData();

            return Response::success($results, '所有格式sitemap生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }
}
