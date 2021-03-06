<?php
class Wsu_WebmasterTools_Adminhtml_SitemappingController extends Mage_Adminhtml_Controller_Action {
    protected function _initAction() {
        $this->loadLayout();
		$this->_setActiveMenu('catalog/wsu_webmastertools_generate');
		$this->_addBreadcrumb(Mage::helper('catalog')->__('Catalog'),
											 Mage::helper('catalog')->__('Catalog')
							)
				->_addBreadcrumb(Mage::helper('wsu_webmastertools')->__('Extended Site mapping'),
								 Mage::helper('wsu_webmastertools')->__('Generate')
								 );
        return $this;
    }
    public function indexAction() {
        $this->_initAction();
        //$this->_addContent($this->getLayout()->createBlock('wsu_webmastertools/adminhtml_sitemapping'));
        $this->renderLayout();
    }	
    /*public function indexAction() {
        $this->_initAction();
        $this->renderLayout();
        //$this->_initAction()
        //    ->_addContent($this->getLayout()->createBlock('wsu_webmastertools/sitemapping'))
        //    ->renderLayout();

    }*/
    public function newAction() {
        $this->_forward('edit');
    }
	
	public function submitAction() {
		try {
			$id = $this->getRequest()->getParam('sitemap_id');
			$obj = Mage::getModel('wsu_webmastertools/submit');
			$msg = $obj->submit($id);
			Mage::getSingleton('adminhtml/session')->addSuccess($msg);
		} catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}
		$this->_redirectReferer();
	}
	
	
    public function editAction() {
        $id    = $this->getRequest()->getParam('sitemap_id');
        $model = Mage::getModel('wsu_webmastertools/sitemap');
		
		
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('wsu_webmastertools')->__('This sitemap no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
		
		
		
		
        if (!empty($data)) {
            $model->setData($data);
        }
		
        Mage::register('sitemap_sitemap', $model);
		
		$block = $this->getLayout()->createBlock('wsu_webmastertools/adminhtml_sitemapping_edit');

        $this->_initAction()
			->_addBreadcrumb($id ? Mage::helper('wsu_webmastertools')->__('Edit Sitemap') : Mage::helper('wsu_webmastertools')->__('New Sitemap'), $id ? Mage::helper('wsu_webmastertools')->__('Edit Sitemap') : Mage::helper('wsu_webmastertools')->__('New Sitemap'))
			->_addContent($block)
			->renderLayout();
    }
    public function saveAction() {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('wsu_webmastertools/sitemap');
            if ($this->getRequest()->getParam('sitemap_id')) {
                $model->load($this->getRequest()->getParam('sitemap_id'));
                if ($model->getSitemapFilename() && file_exists($model->getPreparedFilename())) {
                    unlink($model->getPreparedFilename());
                }
            }
            $model->setData($data);
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('wsu_webmastertools')->__('Sitemap was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array(
                        'sitemap_id' => $model->getId()
                    ));
                    return;
                }
                if ($this->getRequest()->getParam('generate')) {
                    $this->getRequest()->setParam('sitemap_id', $model->getId());
                    $this->_forward('generate');
                    return;
                }
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array(
                    'sitemap_id' => $this->getRequest()->getParam('sitemap_id')
                ));
                return;
            }
        }
        $this->_redirect('*/*/');
    }
    public function deleteAction() {
        if ($id = $this->getRequest()->getParam('sitemap_id')) {
            try {
                $model = Mage::getModel('wsu_webmastertools/sitemap');
                $model->setId($id);
                /* @var $sitemap Wsu_WebmasterTools_Model_Sitemap */
                $model->load($id);
                if ($model->getSitemapFilename() && file_exists($model->getPreparedFilename())) {
                    unlink($model->getPreparedFilename());
                }
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('wsu_webmastertools')->__('Sitemap was successfully deleted'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array(
                    'sitemap_id' => $id
                ));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('wsu_webmastertools')->__('Unable to find a sitemap to delete'));
        $this->_redirect('*/*/');
    }
    public function generateAction() {
        //        $sitemapId = intval($this->getRequest()->getParam('sitemap_id'));        
        //        $sitemap = Mage::getModel('wsu_webmastertools/sitemap')->load($sitemapId);        
        //        $sitemap->generateXml();
        //        exit;
        $this->loadLayout();
        $this->renderLayout();
    }
    public function runGenerateAction() {
        $sitemapId = intval($this->getRequest()->getParam('sitemap_id'));
        if (!$sitemapId)
            return false;
        $sitemap = Mage::getModel('wsu_webmastertools/sitemap')->load($sitemapId);
        if (!$sitemap->getId())
            return false;
        $action = $this->getRequest()->getParam('action', '');
        if (!$action)
            return false;
        $sitemap->_sitemapInc = intval($this->getRequest()->getParam('sitemap_inc', 1));
        $sitemap->_linkInc    = intval($this->getRequest()->getParam('link_inc', 0));
        $sitemap->_currentInc = intval($this->getRequest()->getParam('current_inc', 0));
        $sitemap->generateXml($action);
        $result = array();
        // 'category', 'product', 'tag', 'cms', 'additional_links', 'sitemap_finish'
        switch ($action) {
            case 'category':
                $result['text'] = $this->__('Generated categories (100%)...');
                $action         = 'product';
                break;
            case 'product':
                if ($sitemap->_currentInc >= $sitemap->_totalProducts) {
                    $result['text']       = $this->__('Generated products (100%)...');
                    $sitemap->_currentInc = 0;
                    $action               = 'tag';
                } else {
                    $result['text'] = $this->__('Generating products, processed %2$s of %1$s products (%3$s%%)...', $sitemap->_totalProducts, $sitemap->_currentInc, round($sitemap->_currentInc * 100 / $sitemap->_totalProducts, 2));
                }
                break;
            case 'tag':
                $result['text'] = $this->__('Generated tags (100%)...');
                $action         = 'cms';
                break;
            case 'cms':
                $result['text'] = $this->__('Generated CMS pages (100%)...');
                $action         = 'additional_links';
                break;
            case 'additional_links':
                $result['text'] = $this->__('Generated additional links (100%)...');
                $action         = 'sitemap_finish';
                break;
            case 'sitemap_finish':
                $result['text'] = $this->__('Generated sitemap index (100%)...');
                $result['stop'] = 1;
                $sitemapurl     = Mage::app()->gethelper('wsu_webmastertools/data')->getSitemapUrl();
                $fileName       = preg_replace('/^\//', '', $sitemap->getSitemapPath() . $sitemap->getSitemapFilename());
                $url            = Mage::app()->getStore($sitemap->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . $fileName;
				
				$submission_enabled = Mage::helper('wsu_webmastertools')->getConfig('sitemapsubmission/enabled');
				
				if($submission_enabled>0){
					$submit_url = "http://www.google.com/webmasters/sitemaps/ping?sitemap=".$url;
					/*if(function_exists("curl_init")) {
						$ch = curl_init();
						$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
						curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
						curl_setopt($ch, CURLOPT_URL, $submit_url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
						curl_setopt($ch, CURLOPT_FAILONERROR, true);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($ch, CURLOPT_AUTOREFERER, true);
						curl_setopt($ch, CURLOPT_TIMEOUT, 10);
						$content = curl_exec($ch);
						curl_close($ch);
						//die("curl_init".$content);
					} else {*/
						$content = file_get_contents($submit_url);
						//die("file_get_contents".$content);
					//}
					if(strpos($content, "Sitemap has been successfully added" ) !== false){
						$actionmess= "The sitemap was generated successfully and submitted to Google as %s";
					}else{
						$actionmess= "The sitemap was generated successfully BUT FAILED to be submitted to Google as %s";
					}
				}else{
					$actionmess= '<p>You can submit your Sitemap <a href="http://www.google.com/webmasters/tools/" target="_blank">Webmaster Tools</a> directly or<a target="_blank" href="http://www.google.com/webmasters/sitemaps/ping?sitemap=%s">Ping  Google</a>.</p>';	
				}
                $mess = Mage::helper('wsu_webmastertools')->__($actionmess, $url);

                $this->_getSession()->addSuccess(Mage::helper('wsu_webmastertools')->__('Sitemap "%s" has been successfully generated %s', $url, $mess));
                $action = '';
                break;
        }
        $result['url'] = $this->getUrl('*/*/runGenerate/', array(
            'sitemap_id' => $sitemapId,
            'action' => $action,
            'sitemap_inc' => $sitemap->_sitemapInc,
            'link_inc' => $sitemap->_linkInc,
            'current_inc' => $sitemap->_currentInc
        ));
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/wsu_webmastertools');
    }
}