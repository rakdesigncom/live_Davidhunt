<?php
/**
 * Mega Menu HTML Block.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Block;

class Topmenu extends MenuLayouts
{
    /**
     * Get Store Base URL
     *
     * @return string
     */
    public function getStoreBaseUrl()
    {
        if (!$this->_baseUrl) {
            $this->_baseUrl = $this->storeManager->getStore()->getBaseUrl();
        }

        return $this->_baseUrl;
    }

    /**
     * Category Collection
     *
     * @return array
     */
    public function getCategoryCollection()
    {
        if (!$this->_categoryCollection) {
            $categories = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('is_active', ['eq'=>1])
                ->addAttributeToFilter('include_in_menu', ['eq'=>1])
                ->setStore($this->_customhelper->getStoreId());

            $categoryCollection = [];
            foreach ($categories as $maincat) {
                $categoryCollection[$maincat['entity_id']] = $maincat->debug();
            }
            $this->_categoryCollection = $categoryCollection;
        }

        return $this->_categoryCollection;
    }

    /**
     * Support for get attribute value with HTML
     *
     * @param $content
     * @return mixed|string
     */
    public function getBlockContent($content = '')
    {
        if ($content == '') {
			return null;
		}
        if (!$this->_filterProvider) {
            return $content;
        }
        $content = trim($content);
        return $this->_filterProvider->getBlockFilter()->filter($content);
    }

    /**
     * Check if current page is home
     *
     * @return bool
     */
    public function getIsHomePage()
    {
        $currentUrl = $this->getUrl('', ['_current' => true]);
        $urlRewrite = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        return $currentUrl == $urlRewrite;
    }

    /**
     * Return categories helper
     *
     * @return mixed
     */
    public function getCategoryHelper()
    {
        return $this->_categoryHelper;
    }

    /**
     * Return top menu html
     *
     * @return mixed
     */
    public function getHtml()
    {
        return $this->topMenu->getHtml();
    }

    /**
     * Retrieve current store categories
     *
     * @param $sorted
     * @param $asCollection
     * @param $toLoad
     * @return mixed
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted, $asCollection, $toLoad);
    }

    /**
     * Retrieve child store categories
     *
     * @param $category
     * @return array
     */
    public function getChildCategories($category)
    {
        $children = [];
        if ($this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource()) {
            $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
        foreach ($subcategories as $category) {
            if (!$category->getIsActive()) {
                continue;
            }
            $children[] = $category;
        }
        return $children;
    }

    /**
     * Get info about category by category id
     *
     * @param $id
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    public function getCategoryById($id)
    {
        return $this->categoryRepository->get($id, $this->_customhelper->getStoreId());
    }

    protected function headerAreaHtml($main_cat)
    {
        $catHtml = '';
        if ($main_cat->getMegamenuTypeHeader() != '') {
            $catHtml .= '<div class="menuheader root-col-1 clearfix">';
            $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_header'));
            $catHtml .= '</div>';
        }

        return $catHtml;
    }

    protected function footerAreaHtml($main_cat)
    {
        $catHtml = '';
        if ($main_cat->getMegamenuTypeFooter() != '') {
            $catHtml .= '<div class="menufooter root-col-1 clearfix">';
            $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_footer'));
            $catHtml .= '</div>';
        }

        return $catHtml;
    }

    protected function leftSideContentAreaHtml($main_cat, $left_content_area)
    {
        $catHtml = '<div class="'.$left_content_area.' clearfix rootmegamenu_block">';
        $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_leftblock'));
        $catHtml .= '</div>';

        return $catHtml;
    }

    protected function rightSideContentAreaHtml($main_cat, $right_content_area)
    {
        $catHtml = '<div class="'.$right_content_area.' clearfix rootmegamenu_block">';
        $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_rightblock'));
        $catHtml .= '</div>';

        return $catHtml;
    }

    /**
     * // 4th Level Category
     *
     * @param $navCnt0
     * @param $navCnt
     * @param $navCnt1
     * @param $collection_sub_sub
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function levelFourCategoriesWithLabelHtml($navCnt0, $navCnt, $navCnt1, $collection_sub_sub)
    {
        $catHtml = '';
        if (count($collection_sub_sub)) {
            $catHtml .= ' <ul class="rootmenu-submenu-sub-sub">';
            $navCnt2 = 0;
            foreach ($collection_sub_sub as $childCategory3) {
                $navCnt2++;
                $load_sub_3 = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . '-' . $navCnt1 . '-' . $navCnt2 . ' category-item"><a href="' . $load_sub_3->getURL() . '">' . $childCategory3->getName();
                if ($load_sub_3->getMegamenuTypeLabeltx() != '') {
                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #' . $load_sub_3->getMegamenuTypeLabelclr() . '">' . $load_sub_3->getMegamenuTypeLabeltx() . '</em></span>';
                }
                $catHtml .= '</a></li>';
            }
            $catHtml .= '</ul>';
        }

        return $catHtml;
    }

    /**
     * @param $mainCat
     * @param $childrenCategories_3
     * @param $navCnt0
     * @param $navCnt
     * @param $navCnt1
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function levelFourCategoriesWithImageHtml($mainCat, $childrenCategories_3, $navCnt0, $navCnt, $navCnt1)
    {
        $catHtml = '';
        if (count($childrenCategories_3)) {
            $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
            $catHtml .= '<ul class="level4-popup">';

            $navCnt2 = 0;
            foreach ($childrenCategories_3 as $childCategory3) {
                $navCnt2++;
                $load_cat_sub_sub = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . '-' . $navCnt1 . '-' . $navCnt2 . ' category-item"><a href="' . $load_cat_sub_sub->getURL() . '">';
                $catHtml .= $this->getImageHtml($mainCat, $load_cat_sub_sub, 3);
                $catHtml .= '<span class="level4-name">' . $load_cat_sub_sub->getName() . '</span></a></li>';
            }
            $catHtml .= '</ul>';
        }

        return $catHtml;
    }

    public function getImageHtml($mainCat, $currentCat, $catLevel)
    {
        if ($catLevel == 1) {
            $w = !empty($mainCat->getMegamenuShowCatimageWidth()) ? $mainCat->getMegamenuShowCatimageWidth().'px' : '';
            $h = !empty($mainCat->getMegamenuShowCatimageHeight()) ? $mainCat->getMegamenuShowCatimageHeight().'px' : '';
            $arrowHtml = '<span class="cat-arrow"></span>';
        } else {
            $w = '25px';
            $h = '25px';
            $arrowHtml = ''; // This is for sub-sub categoires layout. Arrow is not requried for sub-sub categories.
        }

        if ($mainCat->getMegamenuShowCatimage() == 1) {
            if ($this->_customhelper->getMegaMenuImageName($currentCat) != '') {
                $imageurl = $this->_customhelper->getMegaMenuImageUrl($currentCat);
            } else {
                $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
            }
            $widthHeightHtml = !empty($w) && !empty($h) ? 'style="width:'.$w.'; height:'.$h.';"' : '';
            $image_html = '<img class="cat_img_as_icon" '.$widthHeightHtml.' src="'.$imageurl.'" alt="'.$currentCat->getName().'" />';
        } else {
            $image_html = $arrowHtml;
        }
        return $image_html;
    }

    public function getImageHtmlAsTitle($mainCat, $currentCat, $catLevel)
    {
        $image_html = '';
        if ($mainCat->getMegamenuShowCatimage() == 1 && $this->_customhelper->getMegaMenuImageName($currentCat) != '') {
            $imageurl = $this->_customhelper->getMegaMenuImageUrl($currentCat);
            $image_html = '<a href="'.$currentCat->getURL().'" class="catproductimg"><img  '.$this->getImgWidthHeight($mainCat).' src="'.$imageurl.'" alt="'.$currentCat->getName().'" /></a>';
        }
        return $image_html;
    }

    /**
     * Simple Mega Menu HTML Block.
     *
     * @param $category
     * @param $navCnt0
     * @return string
     */
    public function simpletMenu($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $catHtml = '';
        // 2nd Level Category
        if ($childrenCategories = $this->getChildCategories($category)) {
            $dropdown_pos = '';
            if ($main_cat->getMegamenuTypeHalfPos() == 1) {
                $dropdown_pos .= ' dropdown-leftside';
            }
            $catHtml .= '<ul class="rootmenu-submenu '.$dropdown_pos.'">';
            $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $navCnt++;
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $collection_sub = $this->getChildCategories($childCategory);
                if (count($collection_sub)) {
                    $arrow = '<span class="cat-arrow"></span>';
                } else {
                    $arrow = '';
                }
                $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.' category-item"><a href="'.$load_cat->getURL().'">'.$childCategory->getName().$arrow;
                if ($load_cat->getMegamenuTypeLabeltx() != '') {
                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat->getMegamenuTypeLabelclr().'">'.$load_cat->getMegamenuTypeLabeltx().'</em></span>';
                }
                $catHtml .= '</a>';

                    // 3rd Level Category
                if (count($collection_sub)) {
                    $catHtml .= '<ul class="rootmenu-submenu-sub">';
                        $navCnt1 = 0;
                    foreach ($collection_sub as $childCategory2) {
                        $navCnt1++;
                        $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                        $collection_sub_sub = $this->getChildCategories($childCategory2);
                        if (count($collection_sub_sub)) {
                            $arrow = '<span class="cat-arrow"></span>';
                        } else {
                            $arrow = '';
                        }
                        $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.' category-item"><a href="'.$load_cat_sub->getURL().'">'.$childCategory2->getName().$arrow;
                        if ($load_cat_sub->getMegamenuTypeLabeltx() != '') {
                            $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat_sub->getMegamenuTypeLabelclr().'">'.$load_cat_sub->getMegamenuTypeLabeltx().'</em></span>';
                        }
                        $catHtml .= '</a>';

                        // 4th Level Category
                        $catHtml .= $this->levelFourCategoriesWithLabelHtml($navCnt0, $navCnt, $navCnt1, $collection_sub_sub);

                        $catHtml .= '</li>';
                    }
                        $catHtml .= '</ul>';
                }
                $catHtml .= '</li>';
            }
            $catHtml .= '</ul>';
        }
        return $catHtml;
    }

    /**
     * Mega Dropdown HTML Block.
     *
     * @param $category
     * @param $navCnt0
     * @return string
     */
    public function megadropdown($category, $navCnt0)
    {
        $categoryHelper = $this->getCategoryHelper();
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $main_cat->getCollection()->addAttributeToFilter('include_in_menu', '1');

        $catHtml = '';

        $colnum = 2;
        /*
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 2;
        }
        */

        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;

        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="verticalmenu02 clearfix">';
            $catHtml .= '<ul class="vertical-list clearfix">';
            foreach ($childrenCategories as $childCategory) {
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $load_cat->getCollection()->addAttributeToFilter('include_in_menu', '1');

                $catHtml .= '<li class="rootverticalnav">';
                    //$catHtml .= '<a href="'.$load_cat->getURL().'">'.$load_cat->getName().'</a>';
                    $catHtml .= '<a href="'.$load_cat->getURL().'">';
                if ($this->_customhelper->getMegaMenuImageName($load_cat) != '') {
                    $catHtml .= '<span class="main-category-name">';
                    $catHtml .= '<i class="main-category-icon"><img src="'.$this->_customhelper->getMegaMenuImageUrl($load_cat).'" alt="'.$load_cat->getName().'" /></i>';
                    $catHtml .= '<em>'.$load_cat->getName().'</em>';
                    $catHtml .= '</span>';
                } else {
                    $catHtml .= $load_cat->getName();
                }
                        $catHtml .= '</a>';
                if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                    $catHtml .= '<div class="v_halfmenu varticalmenu_main halfwidth clearfix">';
                    //$catHtml .= '<div class="vertical_fullwidthmenu varticalmenu_main fullwidth clearfix">';
                    $catHtml .= '<div class="root-col-1 clearfix">';
                        //$catHtml .= '<div class="root-sub-col-3 clearfix"><img src="http://magento2demo.rootways.com/pub/media/catalog/category/party-blazers.jpg" alt="Dropdown Title"></div>';
                        $catWitdh = 6;
                    if ($load_cat->getData('megamenu_type_leftblock') != '') {
                        $catHtml .= '<div class="root-sub-col-3 clearfix">';
                            $catHtml .= $this->getBlockContent($load_cat->getData('megamenu_type_leftblock'));
                        $catHtml .= '</div>';
                    } else {
                        $catWitdh = $catWitdh + 3;
                    }
                    if ($load_cat->getData('megamenu_type_rightblock') == '') {
                        $catWitdh = $catWitdh + 3;
                    }
                        $catHtml .= '<div class="root-sub-col-'.$catWitdh.' clearfix">';
                            $cnt = 1;
                            $cat_tot = count($childrenCategories);
                            $brk = ceil($cat_tot/$colnum);
                            // 2nd Level Category
                            $navCnt = 0;
                    foreach ($childrenCategories_2 as $childCategory2) {
                        $catHtml .= '<div class="root-col-'.count($childrenCategories_2).' clearfix">';
                        $navCnt++;
                        $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                        $load_cat_sub->getCollection()->addAttributeToFilter('include_in_menu', '1');

                        if ($main_cat->getMegamenuShowCatimage() == 1) {
                            if ($this->_customhelper->getMegaMenuImageName($load_cat_sub) != '') {
                                $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat_sub);
                            } else {
                                $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                            }

                            $image_html = '<span class="vertical-listing-img"><img '.$this->getImgWidthHeight($main_cat).' src="'.$imageurl.'" alt="'.$load_cat_sub->getName().'" /></span>';
                            $catHtml .= '<a href="'.$load_cat_sub->getURL().'" class="catproductimg"><img width='.$main_cat->getMegamenuShowCatimageWidth().' height='.$main_cat->getMegamenuShowCatimageHeight().' src="'.$imageurl.'" alt="'.$main_cat->getName().'" /></a>';

                        }
                        $catHtml .= '<div class="title nav-'.$navCnt0.'-'.$navCnt.' category-item"><a href="'.$load_cat_sub->getURL().'">'.$load_cat_sub->getName().'</a></div>';

                        if ($childrenCategories_3 = $this->getChildCategories($childCategory2)) {
                            $catHtml .= '<ul class="level3-popup halfwidth-popup-sub-sub">';
                                $navCnt1 = 0;
                            foreach ($childrenCategories_3 as $childCategory3) {
                                        $navCnt1++;
                                        $load_cat_sub2 = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                                        $load_cat_sub2->getCollection()->addAttributeToFilter('include_in_menu', '1');
                                if ($main_cat->getMegamenuShowCatimage() == 1) {
                                    if ($this->_customhelper->getMegaMenuImageName($load_cat_sub2) != '') {
                                            $imageurl_sub = $this->_customhelper->getMegaMenuImageUrl($load_cat_sub2);
                                    } else {
                                                $imageurl_sub = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                                    }
                                    $image_html_sub = '<img style="width:25px; height:25px;" src="'.$imageurl_sub.'" alt="'.$load_cat_sub2->getName().'" />';
                                } else {
                                    $image_html_sub = '';
                                }

                                        $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.' category-item"><a class="clearfix" href="'.$load_cat_sub2->getURL().'">';
                                        $catHtml .= $image_html_sub;
                                        $catHtml .= '<span class="level3-name">'.$load_cat_sub2->getName().'</span>';
                                        $catHtml .= '</a></li>';
                            }
                                    $catHtml .= '</ul>';
                        }
                        $cnt ++;
                        $catHtml .= '</div>';
                    }
                        $catHtml .= '</div>';
                        //$catHtml .= '<div class="root-sub-col-3 clearfix"><img src="http://magento2demo.rootways.com/pub/media/catalog/category/hoodies-for-men.jpg" alt="'.$load_cat->getName().'"></div>';
                    if ($load_cat->getData('megamenu_type_rightblock') != '') {
                        $catHtml .= '<div class="root-sub-col-3 clearfix">';
                            $catHtml .= $this->getBlockContent($load_cat->getData('megamenu_type_rightblock'));
                        $catHtml .= '</div>';
                    }
                    $catHtml .= '</div>';
                    $catHtml .= '</div>';
                }

                        $catHtml .= '</li>';
            }
            $catHtml .= '</ul>';
            $catHtml .= '</div>';
        }
        return $catHtml;
    }

    /**
     * Dropdown with Title Menu HTML Block.
     */
    public function dropdownTitle($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $main_cat->getCollection()->addAttributeToFilter('include_in_menu', '1');
        $catHtml = '';

        $colnum = 1;
        /*
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 1;
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        */
        // 2nd Level Category
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="halfmenu dropdowntitle clearfix">';
            $catHtml .= $this->headerAreaHtml($main_cat);

            $catHtml .= '<div class="root-col-1 clearfix">';
            if ($this->_customhelper->getMegaMenuImageName($main_cat) != '') {
                $main_imageurl = $this->_customhelper->getMegaMenuImageUrl($main_cat);
            } else {
                $main_imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
            }
                    $catHtml .= '<div class="root-sub-col-6 clearfix rootmegamenu_block">';
                        $catHtml .= '<img src="'.$main_imageurl.'" alt="'.$main_cat->getName().'" />';
                        $catHtml .= '<div class="title"><a href="'.$main_cat->getURL().'">'.$main_cat->getName().'</a></div>';
                    $catHtml .= '</div>';

                    $catHtml .= '<div class="root-sub-col-6 clearfix">';
                        $catHtml .= '<ul class="root-col-'.$colnum.' clearfix level2-popup">';
                        $cnt = 1;
                        $cat_tot = count($childrenCategories);
                        $brk = ceil($cat_tot/$colnum);
                        // 3rd Level Category
            foreach ($childrenCategories as $childCategory) {
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $load_cat->getCollection()->addAttributeToFilter('include_in_menu', '1');

                if ($main_cat->getMegamenuShowCatimage() == 1) {
                    if ($this->_customhelper->getMegaMenuImageName($load_cat) != '') {
                        $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat);
                    } else {
                        $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                    }
                    $image_html = '<img '.$this->getImgWidthHeight($main_cat).' src="'.$imageurl.'" alt="'.$load_cat->getName().'" />';
                } else {
                    $image_html = '';
                }

                $catHtml .= '<li><a href="'.$load_cat->getURL().'">';
                $catHtml .= $image_html;
                $catHtml .= '<span class="sub-cat-name" style="height:'.$main_cat->getMegamenuShowCatimageWidth().'px;">'.$load_cat->getName();
                if ($load_cat->getMegamenuTypeLabeltx() != '') {
                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat->getMegamenuTypeLabelclr().'">'.$load_cat->getMegamenuTypeLabeltx().'</em></span>';
                }
                $catHtml .= '</span>';
                if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                    $catHtml .= '<span class="cat-arrow"></span>';
                }
                $catHtml .='</a>';
                if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                    $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                }
                if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                    $catHtml .= '<ul class="level3-popup halfwidth-popup-sub-sub">';
                    foreach ($childrenCategories_2 as $childCategory2) {
                                $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                                $load_cat_sub->getCollection()->addAttributeToFilter('include_in_menu', '1');
                        if ($main_cat->getMegamenuShowCatimage() == 1) {
                            if ($this->_customhelper->getMegaMenuImageName($load_cat_sub) != '') {
                                            $imageurl_sub = $this->_customhelper->getMegaMenuImageUrl($load_cat_sub);
                            } else {
                                        $imageurl_sub = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                            }
                                            $image_html_sub = '<img style="width:25px; height:25px;" src="'.$imageurl_sub.'" alt="'.$load_cat_sub->getName().'" />';
                        } else {
                            $image_html_sub = '';
                        }

                                    $catHtml .= '<li><a class="clearfix" href="'.$load_cat_sub->getURL().'">';
                                    $catHtml .= $image_html_sub;
                                    $catHtml .= '<span class="level3-name">'.$load_cat_sub->getName();
                        if ($load_cat_sub->getMegamenuTypeLabeltx() != '') {
                            $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat_sub->getMegamenuTypeLabelclr().'">'.$load_cat_sub->getMegamenuTypeLabeltx().'</em></span>';
                        }
                                    $catHtml .= '</span></a></li>';
                    }
                                $catHtml .= '</ul>';
                }

                $catHtml .=  '</li>';
                if ($cnt%$brk == 0 && $cnt != $cat_tot) {
                    $catHtml .= '</ul><ul  class="root-col-'.$colnum.' clearfix level2-popup">';
                }
                $cnt ++;
            }
                        $catHtml .= '</ul>';
                    $catHtml .= '</div>';
                $catHtml .= '</div>';
            $catHtml .= $this->footerAreaHtml($main_cat);
            $catHtml .= '</div>';
        }
        return $catHtml;
    }

    /**
     * Half-Width Mega Menu HTML Block.
     */
    public function halfMenu($category, $navCnt0)
    {
        return $this->halfFullHtml($category, $navCnt0, 0);
    }

    public function halfFullHtml($category, $navCnt0, $isFullWidth)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 2;
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $mainWrapperClass = 'halfmenu clearfix';
            if ($isFullWidth == 1) {
                $mainWrapperClass = 'megamenu fullmenu clearfix linksmenu';
            }
            $catHtml .= '<div class="'.$mainWrapperClass.'">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
                //$catHtml .= '<span class="rw-dropdownclose"></span>';
            $catHtml .= $this->headerAreaHtml($main_cat);

            $catHtml .= '<div class="root-col-1 clearfix">';
            if ($left_width != 0) {
                $catHtml .= $this->leftSideContentAreaHtml($main_cat, $left_content_area);
            }
            $catHtml .= '<div class="'.$category_area_width.' clearfix">';
            $catHtml .= '<ul class="root-col-'.$colnum.' clearfix level2-popup">';
            $cnt = 1;
            $cat_tot = count($childrenCategories);
            $brk = ceil($cat_tot/$colnum);
            // 2nd Level Category
            $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $navCnt++;
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $image_html = $this->getImageHtml($main_cat, $load_cat, 1);
                $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.' category-item"><a href="'.$load_cat->getURL().'">';
                $catHtml .= $image_html;
                //$catHtml .= '<span class="level2-name sub-cat-name" '.$this->getHeight($main_cat).'>'.$load_cat->getName() . '</span>';
                $catHtml .= '<span class="level2-name sub-cat-name">'.$load_cat->getName() . '</span>';
                if ($load_cat->getMegamenuTypeLabeltx() != '') {
                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat->getMegamenuTypeLabelclr().'">'.$load_cat->getMegamenuTypeLabeltx().'</em></span>';
                }
                $childrenCategories_2 = $this->getChildCategories($childCategory);
                if ($childrenCategories_2) {
                    $catHtml .= '<span class="cat-arrow"></span>';
                }
                $catHtml .= '</a>';
                if ($childrenCategories_2) {
                    $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                    $catHtml .= '<ul class="level3-popup">';
                    $navCnt1 = 0;
                    foreach ($childrenCategories_2 as $childCategory2) {
                        $navCnt1++;
                        $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                        $image_html_sub = $this->getImageHtml($main_cat, $load_cat_sub, 2);
                        $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.' category-item"><a href="'.$load_cat_sub->getURL().'">';
                        $catHtml .= $image_html_sub;
                        $catHtml .= '<span class="level3-name sub-cat-name">'.$load_cat_sub->getName().'</span>';

                        $childrenCategories_3 = $this->getChildCategories($childCategory2);
                        if ($childrenCategories_3) {
                            $catHtml .= '<span class="cat-arrow"></span>';
                        }
                        $catHtml .= '</a>';
                        $catHtml .= $this->levelFourCategoriesWithImageHtml($main_cat, $childrenCategories_3, $navCnt0, $navCnt, $navCnt1);
                        $catHtml .= '</li>';
                    }
                    $catHtml .= '</ul>';
                }
                $catHtml .=  '</li>';
                if ($cnt%$brk == 0 && $cnt != $cat_tot) {
                    $catHtml .= '</ul><ul class="root-col-'.$colnum.' clearfix level2-popup">';
                }
                $cnt ++;
            }
            $catHtml .= '</ul>';
            $catHtml .= '</div>';
            if ($right_width != 0) {
                $catHtml .= $this->rightSideContentAreaHtml($main_cat, $right_content_area);
            }
            $catHtml .= '</div>';
            $catHtml .= $this->footerAreaHtml($main_cat);
            $catHtml .= '</div></div>';
        }

        return $catHtml;
    }

    public function halfFullContentOnlyHtml($category, $isFullWidth)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        $mainWrapperClass = 'halfmenu clearfix content-only';
        if ($isFullWidth == 1) {
            $mainWrapperClass = 'megamenu fullmenu clearfix linksmenu content-only';
        }
        $catHtml .= '<div class="'.$mainWrapperClass.'">';
        $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
        $catHtml .= $this->headerAreaHtml($main_cat);

        $catHtml .= '<div class="root-col-1 clearfix">';
        if ($left_width != 0) {
            $catHtml .= $this->leftSideContentAreaHtml($main_cat, $left_content_area);
        }
        if ($right_width != 0) {
            $catHtml .= $this->rightSideContentAreaHtml($main_cat, $right_content_area);
        }
            $catHtml .= '</div>';
        $catHtml .= $this->footerAreaHtml($main_cat);
        $catHtml .= '</div></div>';
        return $catHtml;
    }

    /**
     * Half-Width With Title Mega Menu HTML Block.
     */
    public function halfTitleMenu($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());

        $viewMoreAfter = $main_cat->getMegamenuTypeViewmore();
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 2;
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="halfmenu clearfix">';
            $catHtml .= $this->headerAreaHtml($main_cat);
            $catHtml .= '<div class="root-col-1 clearfix">';
            if ($left_width != 0) {
                $catHtml .= $this->leftSideContentAreaHtml($main_cat, $left_content_area);
            }
            $catHtml .= '<div class="'.$category_area_width.' grid clearfix">';
            $cat_cnt = 1;
            // 2nd Level Category
            $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $navCnt++;
                $catHtml .= '<div class="root-col-'.$colnum.' clearfix ">';
                    $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                    $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat);
                    $catHtml .= $this->getImageHtmlAsTitle($main_cat, $load_cat, 1);

                    $catHtml .= '<div class="title nav-'.$navCnt0.'-'.$navCnt.' category-item""><a href="'.$load_cat->getURL().'">'.$load_cat->getName().'</a>';
                    if ($load_cat->getMegamenuTypeLabeltx() != '') {
                        $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat->getMegamenuTypeLabelclr().'">'.$load_cat->getMegamenuTypeLabeltx().'</em></span>';
                    }
                    $catHtml .= '</div>';
                    // 3th Level Category
                    if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                        $catHtml .= '<ul class="level3-listing">';
                        $subCatCnt = 0;
                        $navCnt1 = 0;
                        foreach ($childrenCategories_2 as $childCategory2) {
                            $navCnt1++;
                            $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                            if ($subCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                $catHtml .= '<li><a class="view-more" href="'.$load_cat->getURL().'">'.__('View More').'</a></li>';
                                break;
                            }
                            $subCatCnt++;
                            $image_html = $this->getImageHtml($main_cat, $load_cat_sub, 2);
                            $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.' category-item"><a href="'.$load_cat_sub->getURL().'">';
                            $catHtml .= $image_html;
                            $catHtml .= '<span class="level2-name sub-cat-name">'.$load_cat_sub->getName().'</span>';
                            if ($load_cat_sub->getMegamenuTypeLabeltx() != '') {
                                $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat_sub->getMegamenuTypeLabelclr().'">'.$load_cat_sub->getMegamenuTypeLabeltx().'</em></span>';
                            }

                            $childrenCategories_3 = $this->getChildCategories($childCategory2);
                            if (count($childrenCategories_3)) {
                                $catHtml .= '<span class="cat-arrow"></span>';
                            }
                            $catHtml .= '</a>';

                            // 4th Level Category
                            if (count($childrenCategories_3)) {
                                $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                                $catHtml .= '<ul class="level4-listing">';
                                $subSubCatCnt = 0;
                                $navCnt2 = 0;
                                foreach ($childrenCategories_3 as $childCategory3) {
                                    $navCnt2++;
                                    if ($subSubCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                        $catHtml .= '<li><a class="view-more" href="'.$load_cat_sub->getURL().'"><span class="level3-name">'.__('View More').'</span></a></li>';
                                        break;
                                    }
                                    $subSubCatCnt++;

                                    $load_cat_sub2 = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                                    $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.'-'.$navCnt2.' category-item"><a href="'.$load_cat_sub2->getURL().'">';
                                    //$catHtml .= $image_html_sub;
                                    $catHtml .= '<span class="level3-name">'.$load_cat_sub2->getName().'</span>';
                                    $catHtml .='</a></li>';
                                }
                                $catHtml .= '</ul>';
                            }
                            $catHtml .= '</li>';
                        }
                        $catHtml .= '</ul>';
                    }
                    $catHtml .= '</div>';
                    /*
                    if ( $cat_cnt%$rightcol_type_num_of_col==0 ) {
                        $catHtml .= '<div class="clearfix"></div>';
                    }
                    */
                    $cat_cnt++;
            }
            $catHtml .= '</div>';

            if ($right_width != 0) {
                $catHtml .= $this->rightSideContentAreaHtml($main_cat, $right_content_area);
            }
            $catHtml .= '</div>';

            $catHtml .= $this->footerAreaHtml($main_cat);
            $catHtml .= '</div>';
        }

        return $catHtml;
    }

    /**
     * Half-Width Menu With Content Only HTML Block.
     */
    public function halfMenuContentOnly($category, $navCnt0)
    {
        return $this->halfFullContentOnlyHtml($category, 0);
    }

    /**
     * Half-Width with Image and Title Only Mega Menu HTML Block.
     */
    public function halfMenuImageOnly($category, $navCnt0)
    {
        $catHtml = $this->getHtmlImageOnly('half', $category, $navCnt0);
        return $catHtml;
    }

    /**
     * Full-Width Mega Menu HTML Block.
     */
    public function fullWidthMenu($category, $navCnt0)
    {
          return $this->halfFullHtml($category, $navCnt0, '1');
    }

    /**
     * Full-Width With Right Side Content Mega Menu HTML Block.
     */
    public function fullTitleMenu($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $viewMoreAfter = $main_cat->getMegamenuTypeViewmore();
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 2;
        }
        $subColnum = $main_cat->getMegamenuTypeSubcol();
        if ($subColnum == 0) {
            $subColnum = 1;
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $msClasses = $this->masonryCategoryClass($category->getId());
        $masonryClass = $msClasses[0];
        $colClass = $msClasses[1];
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="megamenu fullmenu clearfix categoriesmenu">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
            $catHtml .= $this->headerAreaHtml($main_cat);

            $catHtml .= '<div class="root-col-1 clearfix">';
            if ($left_width != 0) {
                $catHtml .= '<div class="'.$left_content_area.' clearfix left rootmegamenu_block">';
                  $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_leftblock'));
                $catHtml .= '</div>';
                $catHtml .= '<div class="main_categoryblockcontent">'.$this->getBlockContent($main_cat->getData('megamenu_type_leftblock')).'</div>';
            } else {
                 $catHtml .= '<div class="main_categoryblockcontent">'.$this->getBlockContent($main_cat->getData('megamenu_type_rightblock')).'</div>';
            }
            $catHtml .= '<div class="'.$category_area_width.' clearfix'.$masonryClass.'">';
            $cat_cnt = 1;

            // 2rd Level Category
            $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $navCnt++;
                $catHtml .= '<div class="'.$colClass.$colnum.' clearfix ">';
                    $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                    $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat);
                    $catHtml .= $this->getImageHtmlAsTitle($main_cat, $load_cat, 1);

                    $catHtml .= '<div class="title nav-'.$navCnt0.'-'.$navCnt.' category-item"><a href="'.$load_cat->getURL().'">'.$load_cat->getName().'</a>';
                    if ($load_cat->getMegamenuTypeLabeltx() != '') {
                         $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat->getMegamenuTypeLabelclr().'">'.$load_cat->getMegamenuTypeLabeltx().'</em></span>';
                    }
                    $catHtml .= '</div>';
                    // 3th Level Category
                    if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                            $catHtml .= '<ul class="level3-listing root-col-'.$subColnum.' clearfix">';
                            $subCatCnt = 0;
                            $cat_tot = count($childrenCategories_2);
                            $brk = ceil($cat_tot/$subColnum);
                            $navCnt1 = 0;
                            foreach ($childrenCategories_2 as $childCategory2) {
                                $navCnt1++;
                                $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                                if ($subCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                    $catHtml .= '<li><a class="view-more" href="'.$load_cat->getURL().'">'.__('View More').'</a></li>';
                                    break;
                                }
                                $subCatCnt++;
                                $image_html = $this->getImageHtml($main_cat, $load_cat_sub, 2);
                                $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.' category-item"><a href="'.$load_cat_sub->getURL().'">';
                                $catHtml .= $image_html;
                                $catHtml .= '<span class="level2-name sub-cat-name">'.$load_cat_sub->getName().'</span>';
                                if ($load_cat_sub->getMegamenuTypeLabeltx() != '') {
                                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat_sub->getMegamenuTypeLabelclr().'">'.$load_cat_sub->getMegamenuTypeLabeltx().'</em></span>';
                                }
                                $childrenCategories_3 = $this->getChildCategories($childCategory2);
                                if (count($childrenCategories_3)) {
                                    $catHtml .= '<span class="cat-arrow"></span>';
                                }
                                $catHtml .= '</a>';

                                /*
                                $catHtml .= '<li><a href="'.$load_cat_sub->getURL().'">'.$load_cat_sub->getName();
                                if ($load_cat_sub->getMegamenuTypeLabeltx() != '') {
                                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat_sub->getMegamenuTypeLabelclr().'">'.$load_cat_sub->getMegamenuTypeLabeltx().'</em></span>';
                                }
                                $childrenCategories_3 = $load_cat_sub->getChildrenCategories();
                                if (count($childrenCategories_3)) {
                                    $catHtml .= '<span class="cat-arrow"></span>';
                                }
                                $catHtml .= '</a>';
                                */
                                if ($left_width != 0 && $load_cat_sub->getData('megamenu_type_leftblock') != '') {
                                              $catHtml .= '<div class="categoryblockcontent">'.$this->getBlockContent($load_cat_sub->getData('megamenu_type_leftblock')).'</div>';
                                } elseif ($right_width != 0 && $load_cat_sub->getData('megamenu_type_rightblock') != '') {
                                      $catHtml .= '<div class="categoryblockcontent">'.$this->getBlockContent($load_cat_sub->getData('megamenu_type_rightblock')).'</div>';
                                } if ($left_width != 0 && $load_cat->getData('megamenu_type_leftblock') != '') {
                                          $catHtml .= '<div class="categoryblockcontent">'.$this->getBlockContent($load_cat->getData('megamenu_type_leftblock')).'</div>';
                                } elseif ($right_width != 0 && $load_cat->getData('megamenu_type_rightblock') != '') {
                                    $catHtml .= '<div class="categoryblockcontent">'.$this->getBlockContent($load_cat->getData('megamenu_type_rightblock')).'</div>';
                                } else {

                                }

                                // 4th Level Category
                                if (count($childrenCategories_3)) {
                                    $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                                    $catHtml .= '<ul class="level4-listing">';
                                    $subSubCatCnt = 0;
                                    $navCnt2 = 0;
                                    foreach ($childrenCategories_3 as $childCategory3) {
                                        $navCnt2++;
                                        if ($subSubCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                                                       $catHtml .= '<li><a class="view-more" href="'.$load_cat_sub->getURL().'"><span class="level3-name">'.__('View More').'</span></a></li>';
                                                                       break;
                                        }
                                          $subSubCatCnt++;

                                          $load_cat_sub2 = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                                          $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.'-'.$navCnt2.' category-item"><a href="'.$load_cat_sub2->getURL().'">';
                                            //$catHtml .= $image_html_sub;
                                          $catHtml .= '<span class="level3-name">'.$load_cat_sub2->getName().'</span>';
                                          $catHtml .='</a>';

                                          // 5th Level Category
                                          /*if ($childrenCategories_4 = $this->getChildCategories($childCategory3)) {
                                            $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                                            $catHtml .= '<ul class="level5-listing">';
                                                $subSubSubCatCnt = 0;
                                                foreach ($childrenCategories_4 as $childCategory4) {
                                                    if ($subSubSubCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                                        $catHtml .= '<li><a class="view-more" href="'.$load_cat_sub->getURL().'"><span class="level3-name">'.__('View More').'</span></a></li>';
                                                        break;
                                                    }
                                                    $subSubSubCatCnt++;

                                                    $load_cat_sub3 = $this->categoryRepository->get($childCategory4->getId(), $this->_customhelper->getStoreId());
                                                    $catHtml .= '<li><a href="'.$load_cat_sub3->getURL().'">';
                                                    //$catHtml .= $image_html_sub;
                                                    $catHtml .= '<span class="level3-name">'.$load_cat_sub3->getName().'</span>';
                                                    $catHtml .= '</a>';
                                                    $catHtml .='</li>';
                                                }
                                            $catHtml .= '</ul>';
                                        }*/
                                        $catHtml .='</li>';
                                    }
                                    $catHtml .= '</ul>';
                                }
                                $catHtml .= '</li>';
                                if ($subCatCnt%$brk == 0 && $subCatCnt != $cat_tot) {
                                    $catHtml .= '</ul><ul class="level3-listing root-col-'.$subColnum.' clearfix">';
                                }
                            }
                            $catHtml .= '</ul>';
                    }
                    $catHtml .= '</div>';
                    if ($masonryClass != ' grid' && $cat_cnt%$colnum==0) {
                        $catHtml .= '<div class="clearfix"></div>';
                    }
                    $cat_cnt++;
            }
            $catHtml .= '</div>';
            if ($right_width != 0) {
                $catHtml .= '<div class="'.$right_content_area.' clearfix right rootmegamenu_block">';
                   $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_rightblock'));
                $catHtml .= '</div>';
            }
            $catHtml .= '</div>';
            $catHtml .= $this->footerAreaHtml($main_cat);
            $catHtml .= '</div></div>';
        }

        return $catHtml;
    }

    /**
     * Full-Width Content Only Mega Menu HTML Block.
     */
    public function fullWidthContentOnly($category, $navCnt0)
    {
        return $this->halfFullContentOnlyHtml($category, 1);
    }

    /**
     * Full-Width with Image and Title Olny Mega Menu HTML Block.
     */
    public function fullWidthImageOnly($category, $navCnt0)
    {
        $catHtml = $this->getHtmlImageOnly('full', $category, $navCnt0);
        return $catHtml;
    }

    public function getHtmlImageOnly($mType, $category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 5;
            if ($mType == 'half') {
                $colnum = 2;
            }
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        $mainClass = 'megamenu fullmenu clearfix categoriesmenu rwimageonly';
        if ($mType == 'half') {
            $mainClass = 'halfmenu clearfix rwimageonly';
        }
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="'.$mainClass.'">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
            $catHtml .= $this->headerAreaHtml($main_cat);

            $catHtml .= '<div class="root-col-1 clearfix">';
            if ($left_width != 0) {
                $catHtml .= '<div class="'.$left_content_area.' clearfix left rootmegamenu_block">';
                    $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_leftblock'));
                $catHtml .= '</div>';
                $catHtml .= '<div class="main_categoryblockcontent">'.$this->getBlockContent($main_cat->getData('megamenu_type_leftblock')).'</div>';
            } else {
                $catHtml .= '<div class="main_categoryblockcontent">'.$this->getBlockContent($main_cat->getData('megamenu_type_rightblock')).'</div>';

            }
                    $catHtml .= '<div class="'.$category_area_width.' clearfix">';
                        $cat_cnt = 1;
                        // 2rd Level Category

            foreach ($childrenCategories as $childCategory) {
                $catHtml .= '<div class="root-col-'.$colnum.' clearfix ">';
                    $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                    $imgHtml = $this->getImageHtmlAsTitle($main_cat, $load_cat, 1);
                if ($main_cat->getMegamenuTypeImgpos() == '0') {
                            $catHtml .= $imgHtml;
                }
                if ($main_cat->getMegamenuTypeShowtitle() == '1') {
                                $catHtml .= '<div class="title"><a href="'.$load_cat->getURL().'">'.$load_cat->getName().'</a>';
                    if ($load_cat->getMegamenuTypeLabeltx() != '') {
                        $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #'.$load_cat->getMegamenuTypeLabelclr().'">'.$load_cat->getMegamenuTypeLabeltx().'</em></span>';
                    }
                                $catHtml .= '</div>';
                }
                if ($main_cat->getMegamenuTypeImgpos() == '1') {
                    $catHtml .= $imgHtml;
                }
                                        $catHtml .= '</div>';
                if ($cat_cnt%$colnum==0) {
                    $catHtml .= '<div class="clearfix"></div>';
                }

                                        $cat_cnt++;
            }
                    $catHtml .= '</div>';


            if ($right_width != 0) {
                $catHtml .= '<div class="'.$right_content_area.' clearfix right rootmegamenu_block">';
                    $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_rightblock'));
                $catHtml .= '</div>';
            }
                $catHtml .= '</div>';

            $catHtml .= $this->footerAreaHtml($main_cat);
            $catHtml .= '</div></div>';
        }
        return $catHtml;
    }

    /**
     * Tabbing Mega Menu HTML Block.
     */
    public function tabMenu($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());

        $viewMoreAfter = $main_cat->getMegamenuTypeViewmore();
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 5;
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $msClasses = $this->masonryCategoryClass($category->getId());
        $masonryClass = $msClasses[0];
        $colClass = $msClasses[1];
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="megamenu fullmenu clearfix tabmenu">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
            $catHtml .= '<div class="mainmenuwrap clearfix">';
            $catHtml .= '<ul class="root-col-1 clearfix vertical-menu">';
                $cnt = 0;
                 // 2nd Level Category
            foreach ($childrenCategories as $childCategory) {
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $left_sub_width = $load_cat->getMegamenuTypeLeftblockW();
                $right_sub_width = $load_cat->getMegamenuTypeRightblockW();
                $cat_sub_width = 12 - ($left_sub_width + $right_sub_width);
                $sub_category_area_width = 'root-sub-col-'.$cat_sub_width;
                $sub_left_content_area = 'root-sub-col-'.$left_sub_width;
                $sub_right_content_area = 'root-sub-col-'.$right_sub_width;
                if ($left_sub_width != 0 || $right_sub_width != 0) {
                    $category_area_width = 'root-sub-col-'.$cat_sub_width;
                } else {
                    $category_area_width = 'root-sub-col-'.$cat_width;
                }

                if ($cnt == 0) {
                    $open = "main_openactive01";
                } else {
                    $open = "";
                } $cnt++;
                $catHtml .= '<li class="clearfix '.$open.'"><a href="'.$load_cat->getUrl().'" class="root-col-4">';
                $catHtml .= $this->getImageHtml($main_cat, $load_cat, 2);
                $catHtml .= $load_cat->getName().'<span class="cat-arrow"></span></a>';
                if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                        $catHtml .= '<div class="root-col-75 verticalopen">';
                    if ($load_cat->getMegamenuTypeHeader() != '') {
                           $catHtml .= '<div class="menuheader root-col-1 clearfix">';
                               $catHtml .= $this->getBlockContent($load_cat->getData('megamenu_type_header'));
                           $catHtml .= '</div>';
                    } elseif ($main_cat->getMegamenuTypeHeader() != '') {
                        $catHtml .= '<div class="menuheader root-col-1 clearfix">';
                            $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_header'));
                        $catHtml .= '</div>';
                    }
                               $catHtml .= '<div class="padding-zero root-col-1 clearfix">';
                    if ($left_width != 0 || $left_sub_width != 0) {
                            $left_sub_content = $this->getBlockContent($load_cat->getData('megamenu_type_leftblock'));
                        if ($left_sub_content != '') {
                              $catHtml .= '<div class="'.$sub_left_content_area.' clearfix rootmegamenu_block">';
                                  $catHtml .= $left_sub_content;
                              $catHtml .= '</div>';
                        } else {
                            $catHtml .= $this->leftSideContentAreaHtml($main_cat, $left_content_area);
                        }
                    }
                                      $catHtml .= '<div class="'.$category_area_width.' clearfix padding-zero'.$masonryClass.'">';
                                      $sub_cnt = 1;
                                    // 3th Level Category
                    foreach ($childrenCategories_2 as $childCategory2) {
                        if ($main_cat->getMegamenuShowCatimage() == 1) {
                            $brake_point = $colnum * 2;
                        } else {
                            $brake_point = $colnum * 6;
                        }
                        //if ($sub_cnt > $brake_point) { continue; }
                        $sub_cnt++;
                        $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());

                        $catHtml .= '<div class="tabimgwpr '.$colClass.$colnum.' clearfix ">';
                        //$catHtml .= '<div class="tabimgwpr root-col-'.$colnum.'">;
                        $catHtml .= '<a href="'.$load_cat_sub->getURL().'" class="tabimtag">';
                        if ($main_cat->getMegamenuShowCatimage() == 1) {
                            if ($this->_customhelper->getMegaMenuImageName($load_cat_sub) != '') {
                                $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat_sub);
                            } else {
                                $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                            }
                            $catHtml .= '<img width='.$main_cat->getMegamenuShowCatimageWidth().' height='.$main_cat->getMegamenuShowCatimageHeight().' src="'.$imageurl.'" alt="'.$main_cat->getName().'" />';
                        }
                        $catHtml .= '<div class="tabimgtext">'.$load_cat_sub->getName().'</div></a>';

                        if ($childrenCategories_3 = $this->getChildCategories($childCategory2)) {
                            $catHtml .= '<ul class="tabbing_lev4">';
                            $subCatCnt = 0;
                            foreach ($childrenCategories_3 as $childCategory3) {
                                if ($subCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                    $catHtml .= '<li><a class="view-more" href="'.$load_cat_sub->getURL().'">'.__('View More').'</a></li>';
                                    break;
                                }
                                $subCatCnt++;
                                $load_cat_sub_sub = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                                $image_html_sub_sub = $this->getImageHtml($main_cat, $load_cat_sub_sub, 3);
                                $catHtml .= '<li><a href="'.$load_cat_sub_sub->getURL().'">';
                                $catHtml .= $image_html_sub_sub;
                                $catHtml .= '<span class="level4-name">'.$load_cat_sub_sub->getName().'</span></a></li>';
                            }
                            $catHtml .= '</ul>';
                        }
                        $catHtml .= '</div>';
                        if ($masonryClass != ' grid' && $sub_cnt%$colnum==0) {
                            $catHtml .= '<div class="clearfix"></div>';
                        }
                    }
                                    /*
                                    if ($sub_cnt > $brake_point) {
                                        $catHtml .= '<a href="'.$load_cat->getURL().'" class="view_all">View All &raquo;</a>';
                                    }*/
                                      $catHtml .= '</div>';
                    if ($right_width != 0 || $right_sub_width != 0) {
                        $right_sub_content = $this->getBlockContent($load_cat->getData('megamenu_type_rightblock'));
                        if ($right_sub_content != '') {
                            $catHtml .= '<div class="'.$sub_right_content_area.' clearfix rootmegamenu_block">';
                             $catHtml .= $right_sub_content;
                            $catHtml .= '</div>';
                        } else {
                            $catHtml .= $this->rightSideContentAreaHtml($main_cat, $right_content_area);
                        }
                    }
                                      $catHtml .= '</div>';
                    if ($load_cat->getMegamenuTypeFooter() != '') {
                        $catHtml .= '<div class="menufooter root-col-1 clearfix">';
                         $catHtml .= $this->getBlockContent($load_cat->getData('megamenu_type_footer'));
                        $catHtml .= '</div>';
                    } elseif ($main_cat->getMegamenuTypeFooter() != '') {
                        $catHtml .= '<div class="menufooter root-col-1 clearfix">';
                         $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_footer'));
                        $catHtml .= '</div>';
                    } else {

                    }
                        $catHtml .= '</div>';
                } else {
                    $catHtml .= '<div class="root-col-75 verticalopen empty_category">';
                    $catHtml .= '<span>Sub-category not found for '.$load_cat->getName().' Category</span>';
                    $catHtml .= '</div>';
                }
                    $catHtml .= '</li>';
            }
            $catHtml .= '</ul>';
            $catHtml .= '</div>';
            $catHtml .= '</div></div>';
        }
        return $catHtml;
    }

    /**
     * Full-Width Horizontal Mega Menu HTML Block.
     */
    public function tabHorizontal($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="megamenu fullmenu clearfix tabmenu02">';
                $catHtml.= '<div class="mainmenuwrap02 clearfix">';
                    $catHtml .= '<ul class="vertical-menu02 root-col-1 clearfix">';
            foreach ($childrenCategories as $childCategory) {
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $left_sub_width = $load_cat->getMegamenuTypeLeftblockW();
                $right_sub_width = $load_cat->getMegamenuTypeRightblockW();
                $cat_sub_width = 12 - ($left_sub_width + $right_sub_width);
                $sub_category_area_width = 'root-sub-col-'.$cat_sub_width;
                $sub_left_content_area = 'root-sub-col-'.$left_sub_width;
                $sub_right_content_area = 'root-sub-col-'.$right_sub_width;
                if ($left_sub_width != 0 || $right_sub_width != 0) {
                    $category_area_width = 'root-sub-col-'.$cat_sub_width;
                } else {
                    $category_area_width = 'root-sub-col-'.$cat_width;
                }
                $catHtml .= '<li class="clearfix"><a class="clearfix" '.$this->getLineHeight($main_cat).' href="'.$load_cat->getURL().'">';

                /*
                            if ($main_cat->getMegamenuShowCatimage() == 1) {
                                if ($this->_customhelper->getMegaMenuImageName($load_cat) != '') {
                                    $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat);
                                } else {
                                    $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                                }
                                $catHtml .= ' <span><img '.$this->getImgWidthHeight($main_cat).' src="'.$imageurl.'" alt="'.$load_cat->getName().'" /></span>';
                            }
                */
                            $catHtml .= '<em>'.$load_cat->getName().'</em></a>';
                if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                    $num_of_col = $load_cat->getMegamenuTypeNumofcolumns();
                    if ($num_of_col == 0) {
                        $num_of_col = 3;
                    }
                    $cnt = 0;
                    $cat_tot = count($childrenCategories_2);
                    $brk = ceil($cat_tot/$num_of_col);

                    if ($cnt == 0) {
                        $open = "openactive02";

                    } else {
                        $open = "";
                    }
                    $cnt++;
                    $catHtml .= '<div class="root-col-1 verticalopen02 '.$open.'">';
                    $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
                    if ($left_width != 0 || $left_sub_width != 0) {
                                $left_sub_content = $this->getBlockContent($load_cat->getData('megamenu_type_leftblock'));
                        if ($left_sub_content != '') {
                            $catHtml .= '<div class="'.$sub_left_content_area.' clearfix rootmegamenu_block">';
                            $catHtml .= $left_sub_content;
                            $catHtml .= '</div>';
                        } else {
                            $catHtml .= $this->leftSideContentAreaHtml($main_cat, $left_content_area);
                        }
                    }
                        $sub_cnt = 1;
                        $catHtml .= '<div class="'.$category_area_width.' topmenu02-categories clearfix">';
                            $catHtml .= '<div class="title"><a href="'.$load_cat->getURL().'">'.$load_cat->getName().'</a></div>';
                            $catHtml .= '<div class="root-col-'.$num_of_col.' clearfix">';
                                $catHtml .= '<ul class="ulliststy02">';
                                    $sub_cnt = 1;
                    foreach ($childrenCategories_2 as $childCategory2) {
                                $load_sub_sub_cat = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                                $catHtml .= '<li><a href="'.$load_sub_sub_cat->getURL().'">';
                                $catHtml .= $this->getImageHtml($main_cat, $load_sub_sub_cat, 1);
                                $catHtml .= '<span class="level2-name sub-cat-name" '.$this->getHeight($main_cat).'>'.$load_sub_sub_cat->getName().'</span>';
                                $catHtml .= '</a>';

                        if ($sub_cnt%$brk == 0) {
                            $catHtml .= '</ul></div> <div class="root-col-'.$num_of_col.' clearfix"><ul class="ulliststy02">';
                        }
                                $sub_cnt++;
                    }
                                            $catHtml .= '</ul>';
                                            $catHtml .= '</div>';
                                            $catHtml .= '</div>';
                    if ($right_width != 0 || $right_sub_width != 0) {
                        $right_sub_content = $this->getBlockContent($load_cat->getData('megamenu_type_rightblock'));
                        if ($right_sub_content != '') {
                            $catHtml .= '<div class="'.$sub_right_content_area.' clearfix rootmegamenu_block">';
                                $catHtml .= $right_sub_content;
                            $catHtml .= '</div>';
                        } else {
                            $catHtml .= $this->rightSideContentAreaHtml($main_cat, $right_content_area);
                        }
                    }
                                            $catHtml .= '</div></div>';
                } else {
                    $catHtml .= '<div class="root-col-1 verticalopen02">';
                       $catHtml .= '<span>There is no sub-category for '.$load_cat->getName().' category</span>';
                    $catHtml .= '</div>';
                }
                $catHtml .= '</li>';
            }
                    $catHtml .= '</ul>';
                $catHtml .= '</div>';
            $catHtml .= '</div>';
        }
        return $catHtml;
    }

    /**
     * Multi Tabbing Mega Menu HTML Block.
     */
    public function multiTabbing($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $colnum = (int)$main_cat->getMegamenuTypeSubcatlevel();
        $viewMoreAfter = $main_cat->getMegamenuTypeViewmore();
        if ($colnum == '0') {
            $colnum = 4;
        }
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="megamenu fullmenu clearfix fourcoltab multitabcol_'.$colnum.'">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
                $catHtml.= '<div class="mainmenuwrap clearfix">';
                    $catHtml .= '<ul class="colultabone">';
                        $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $navCnt++;
                $openFirstLayer = '';
                if ($navCnt == 1) {
                    $openFirstLayer ='main_openactive03';
                }
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.' category-item clearfix '.$openFirstLayer.'"><a class="clearfix" '.$this->getLineHeight($main_cat).' href="'.$load_cat->getURL().'">';
                    $catHtml .= $this->getImageHtml($main_cat, $load_cat, 2);
                    $catHtml .= '<em>'.$load_cat->getName().'</em></a>';
                if ($colnum >= 2) {
                    if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                        $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                    }
                    if ($childrenCategories_2 = $this->getChildCategories($childCategory)) {
                        $catHtml .= '<ul class="colultabtwo">';
                        $navCnt1 = 0;
                        foreach ($childrenCategories_2 as $childCategory2) {
                            $navCnt1++;
                            $openFirstLayer2 = '';
                            if ($navCnt == 1 && $navCnt1 == 1) {
                                $openFirstLayer2 ='main_openactive03_sub1';
                            }
                            $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                            $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.' category-item clearfix '.$openFirstLayer2.'"><a class="clearfix" '.$this->getLineHeight($main_cat).' href="'.$load_cat_sub->getURL().'">';
                                $catHtml .= $this->getImageHtml($main_cat, $load_cat_sub, 2);
                                $catHtml .= '<em>'.$load_cat_sub->getName().'</em></a>';
                            if ($colnum >= 3) {
                                if ($childrenCategories_3 = $this->getChildCategories($childCategory2)) {
                                        $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                                }
                                if ($childrenCategories_3 = $this->getChildCategories($childCategory2)) {

                                                    $catHtml .= '<ul class="colultabthree">';
                                                        $navCnt2 = 0;
                                    foreach ($childrenCategories_3 as $childCategory3) {
                                        $navCnt2++;
                                        $openFirstLayer3 = '';
                                        if ($navCnt == 1 && $navCnt1 == 1 && $navCnt2 == 1) {
                                                $openFirstLayer3 ='main_openactive03_sub2';
                                        }
                                                $load_cat_sub_2 = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                                                $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.'-'.$navCnt1.'-'.$navCnt2.' category-item clearfix '.$openFirstLayer3.'"><a class="clearfix" '.$this->getLineHeight($main_cat).' href="'.$load_cat_sub_2->getURL().'">';
                                                $catHtml .= $this->getImageHtml($main_cat, $load_cat_sub_2, 2);
                                                $catHtml .= '<em>'.$load_cat_sub_2->getName().'</em></a>';
                                        if ($colnum >= 4) {
                                            if ($childrenCategories_4 = $this->getChildCategories($childCategory3)) {
                                                $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                                            }
                                            if ($childrenCategories_4 = $this->getChildCategories($childCategory3)) {
                                                $catHtml .= '<div class="resultdiv clearfix">';
                                                    $subCatCnt = 0;
                                                foreach ($childrenCategories_4 as $childCategory4) {
                                                                $load_cat_sub_3 = $this->categoryRepository->get($childCategory4->getId(), $this->_customhelper->getStoreId());
                                                    if ($subCatCnt >= $viewMoreAfter && $viewMoreAfter != '') {
                                                        $catHtml .= '<div class="root-col-1"><a class="view-more" href="'.$load_cat_sub_2->getURL().'">'.__('View More').'</a></div>';
                                                        break;
                                                    }
                                                                $subCatCnt++;
                                                                $catHtml .= '<div class="root-col-3">';
                                                    if ($main_cat->getMegamenuShowCatimage() == 1) {
                                                        if ($this->_customhelper->getMegaMenuImageName($load_cat_sub_3) != '') {
                                                                                             $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat_sub_3);
                                                        } else {
                                                                               $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                                                        }
                                                                $catHtml .= ' <span class="productbtmimg"><img src="'.$imageurl.'" alt="'.$load_cat_sub_3->getName().'" /></span>';
                                                    }
                                                                    $catHtml .=  '<a href="'.$load_cat_sub_3->getURL().'" class="productbtmname">'.$load_cat_sub_3->getName().'</a>';
                                                                 $catHtml .= '</div>';
                                                }
                                                            $catHtml .= '</div>';
                                            }
                                        }
                                                        $catHtml .= '</li>';
                                    }
                                                    $catHtml .= '</ul>';
                                }
                            }
                                        $catHtml .= '</li>';
                        }
                                $catHtml .= '</ul>';
                    } else {
                         $catHtml .= '<div class="colultabonenofound clearfix">Sub-category not found for '.$load_cat->getName().' Category</div>';
                    }
                }
                            $catHtml .= '</li>';
            }
                    $catHtml .= '</ul>';
                $catHtml .= '</div>';
            $catHtml .= '</div></div>';
        }
        return $catHtml;
    }

    /**
     * Product Listing Mega Menu HTML Block.
     */
    public function productMenu($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $products = $main_cat->getProductCollection();
        $products->addAttributeToSelect('*');
        $catHtml = '';

        $media_url = $this->_customhelper->getMediaUrl();
        $currency = $this->_customhelper->getCurrentCurrencyCode();

        $catHtml .= '<div class="megamenu clearfix product-thumbnail">';
        $catHtml .= '<div class="' . $this->_customhelper->menuContainerClass() . '">';
        $pro_cnt = 0;
        foreach ($products as $product) {
            if ($pro_cnt > 10) {
                continue;
            }
            $pro_cnt++;
            $_product = $this->_customhelper->loadProductById($product->getId());

            $catHtml .= '<div class="root-col-5 clearfix">';
            $catHtml .= '<div class="probox01imgwp">';
            $catHtml .= '<div class="proimg"><a href="' . $_product->getProductUrl() . '"><img src="' . $media_url . 'catalog/product' . $_product->getImage() . '" alt="' . $main_cat->getName() . '" /></a></div>';
            $catHtml .= '</div>';
            $catHtml .= '<div class="proinfo clearfix">';
            $catHtml .= '<div class="proname clearfix"><a href="#">' . $_product->getName() . '</a></div>';
            $catHtml .= '<div class="pricebox"> <span>' . $currency . number_format($_product['price'], 2) . '</span> <a href="' . $_product->getProductUrl() . '" class="addtocart-but">Add to Cart</a> </div>';
            $catHtml .= '</div>';
            $catHtml .= '</div> ';
        }
        $catHtml .= '</div></div>';
        return $catHtml;
    }

    /**
     * Catepgur Product Mega Menu HTML Block.
     */
    public function categoryProductMenu($category, $navCnt0)
    {
        $main_cat = $this->categoryRepository->get($category->getId(), $this->_customhelper->getStoreId());
        $currency = $this->_customhelper->getCurrentStore()->getCurrentCurrencyCode();
        $media_url = $this->_customhelper->getCurrentStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 5;
        }
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $msClasses = $this->masonryCategoryClass($category->getId());
        $masonryClass = $msClasses[0];
        $colClass = $msClasses[1];
        $catHtml = '';
        if ($childrenCategories = $this->getChildCategories($category)) {
            $catHtml .= '<div class="megamenu fullmenu clearfix tabmenu categorywithproductmenu">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
            $catHtml .= '<div class="mainmenuwrap clearfix">';
            $catHtml .= '<ul class="root-col-1 clearfix vertical-menu">';
                $cnt = 0;
                 // 2nd Level Category
            foreach ($childrenCategories as $childCategory) {
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $left_sub_width = $load_cat->getMegamenuTypeLeftblockW();
                $right_sub_width = $load_cat->getMegamenuTypeRightblockW();
                $cat_sub_width = 12 - ($left_sub_width + $right_sub_width);
                $sub_category_area_width = 'root-sub-col-'.$cat_sub_width;
                $sub_left_content_area = 'root-sub-col-'.$left_sub_width;
                $sub_right_content_area = 'root-sub-col-'.$right_sub_width;
                if ($left_sub_width != 0 || $right_sub_width != 0) {
                    $category_area_width = 'root-sub-col-'.$cat_sub_width;
                } else {
                    $category_area_width = 'root-sub-col-'.$cat_width;
                }

                if ($cnt == 0) {
                    $open = "main_openactive01";
                } else {
                    $open = "";
                } $cnt++;
                $catHtml .= '<li class="clearfix '.$open.'"><a href="'.$load_cat->getUrl().'" class="root-col-4">'.$load_cat->getName().'<span class="cat-arrow"></span></a>';
                $products = $load_cat->getProductCollection();
                $products->addAttributeToSelect('*');
                if (count($products) > 0) {
                        $catHtml .= '<div class="root-col-75 padding-zero verticalopen">';
                    if ($load_cat->getMegamenuTypeHeader() != '') {
                           $catHtml .= '<div class="menuheader root-col-1 clearfix">';
                               $catHtml .= $this->getBlockContent($load_cat->getData('megamenu_type_header'));
                           $catHtml .= '</div>';
                    } elseif ($main_cat->getMegamenuTypeHeader() != '') {
                        $catHtml .= '<div class="menuheader root-col-1 clearfix">';
                            $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_header'));
                        $catHtml .= '</div>';
                    }
                               $catHtml .= '<div class="padding-zero root-col-1 clearfix">';
                    if ($left_width != 0 || $left_sub_width != 0) {
                            $left_sub_content = $this->getBlockContent($load_cat->getData('megamenu_type_leftblock'));
                        if ($left_sub_content != '') {
                              $catHtml .= '<div class="'.$sub_left_content_area.' clearfix rootmegamenu_block">';
                                  $catHtml .= $left_sub_content;
                              $catHtml .= '</div>';
                        } else {
                            $catHtml .= $this->leftSideContentAreaHtml($main_cat, $left_content_area);
                        }
                    }
                                      $catHtml .= '<div class="'.$category_area_width.' clearfix padding-zero'.$masonryClass.'">';
                                      $sub_cnt = 0;
                                    // 3th Level Category
                    foreach ($products as $_product) {
                        $brake_point = $colnum * 3;
                        if ($sub_cnt > $brake_point) {
                            continue;
                        }
                        $sub_cnt++;

                        $catHtml .= '<div class="root-col-'.$colnum.'">';
                          $catHtml .= '<div class="htabproductbxleft clearfix"><a href="'.$_product->getProductUrl().'" class="tabimtag"><img src="'.$media_url.'catalog/product'.$_product->getImage().'" alt="'.$main_cat->getName().'" /></a></div>';
                          $catHtml .= '<div class="htabproductbxright clearfix">';
                           $catHtml .= '<div class="htabproductbxhead">'.$_product->getName().'</div>';
                           $catHtml .= '<div class="htabproductbxprice">'.__('Price:').'<span> '.$currency.number_format($_product['price'], 2).'</span></div>';
                           $catHtml .= '<a href="'.$_product->getProductUrl().'" class="htabproductbxcartbtn">'.__('View Product').'</a>';
                          $catHtml .= '</div>';
                        $catHtml .= '</div>';

                        if ($sub_cnt%$colnum==0) {
                            $catHtml .= '<div class="clearfix"></div>';
                        }
                    }
                    if ($sub_cnt > $brake_point) {
                        $catHtml .= '<a href="'.$load_cat->getURL().'" class="view_all">View All &raquo;</a>';
                    }
                                      $catHtml .= '</div>';
                    if ($right_width != 0 || $right_sub_width != 0) {
                        $right_sub_content = $this->getBlockContent($load_cat->getData('megamenu_type_rightblock'));
                        if ($right_sub_content != '') {
                            $catHtml .= '<div class="'.$sub_right_content_area.' clearfix rootmegamenu_block">';
                             $catHtml .= $right_sub_content;
                            $catHtml .= '</div>';
                        } else {
                            $catHtml .= $this->rightSideContentAreaHtml($main_cat, $right_content_area);
                        }
                    }
                                      $catHtml .= '</div>';
                    if ($load_cat->getMegamenuTypeFooter() != '') {
                        $catHtml .= '<div class="menufooter root-col-1 clearfix">';
                         $catHtml .= $this->getBlockContent($load_cat->getData('megamenu_type_footer'));
                        $catHtml .= '</div>';
                    } elseif ($main_cat->getMegamenuTypeFooter() != '') {
                        $catHtml .= '<div class="menufooter root-col-1 clearfix">';
                         $catHtml .= $this->getBlockContent($main_cat->getData('megamenu_type_footer'));
                        $catHtml .= '</div>';
                    } else {

                    }
                        $catHtml .= '</div>';
                } else {
                    $catHtml .= '<div class="root-col-75 verticalopen empty_category">';
                    $catHtml .= '<span>Product does not found for '.$load_cat->getName().' Category</span>';
                    $catHtml .= '</div>';
                }
                    $catHtml .= '</li>';
            }
            $catHtml .= '</ul>';
            $catHtml .= '</div>';
            $catHtml .= '</div></div>';
        }
        return $catHtml;
    }

    /**
     * Contact Us Mega Menu HTML Block.
     */
    public function contactus()
    {
        $contact_form_value = $this->_customhelper->getConfig('rootmegamenu_option/general/show_contactus');
        $contact_form_col = 'root-col-1';

        $catHtml = '';
        $catHtml .= '<li class="contactus_menu"><a href="javascript:void(0);">'.__('Contact Us').'</a>';
            $catHtml .= '<div class="megamenu fullmenu contacthalfmenu clearfix">';
            $catHtml .= '<div class="'.$this->_customhelper->menuContainerClass().'">';
        if ($contact_form_value == 2) {
            $catHtml .= '<div class="root-col-2 clearfix">';
                $contact_content = $this->_customhelper->getConfig('rootmegamenu_option/general/contactus_content');
                $catHtml .= $this->getBlockContent($contact_content);
            $catHtml .= '</div>';
            $contact_form_col = 'root-col-2';
        }
                $base_url = $this->_storeManager->getStore()->getBaseUrl();

                $catHtml .= '<div class="'.$contact_form_col.' clearfix">';
                    $catHtml .= '<div class="title">'.__('Contact Us').'</div>';
                    $catHtml .=    '<form id="megamenu_contact_form" name="megamenu_contact_form" class="menu_form">';
                        $catHtml .= '<input id="name" name="name" type="text" autocomplete="off" placeholder="'.__('Name').'">';
                        $catHtml .= '<input id="menuemail" name="menuemail" type="text" autocomplete="off" placeholder="'.__('Email').'">';
                        $catHtml .= '<input type="text" title="Telephone" id="telephone" name="telephone" autocomplete="off" placeholder="'.__('Telephone').'">';
                        $catHtml .= '<textarea id="comment" name="comment" placeholder="'.__('Your message...').'"></textarea>';
                        $catHtml .= '<input type="text" style="display:none !important;" value="" id="hideit" name="hideit">';
                        $catHtml .= '<input type="text" style="display:none !important;" value="'.$base_url.'" name="base_url" id="base_url" >';
                        $catHtml .= '<input onclick="rootFunction()" type="button" value="'.__('Reset').'">';
                        $catHtml .= '<input id="megamenu_submit" type="submit" value="'.__('Send').'">';
                    $catHtml .= '</form>';
                $catHtml .= '</div>';
            $catHtml .= '</div></div>';
        $catHtml .= '</li>';

        return $catHtml;
    }

    /*
    public function getValuesConfig()
    {
        return $this->_scopeConfig->getValue('rootmegamenu_option/general/licencekey');
    }
    */

    public function _getMenuItemAttributes($item)
    {
        $menuItemClasses = $this->_getMenuItemClasses($item);
        return implode(' ', $menuItemClasses);
    }

    /**
     * Get Class of categories.
     */
    protected function _getMenuItemClasses($item)
    {
        $classes = [];
        if ($this->_customhelper->getConfig('rootmegamenu_option/general/topmenuarrow') == 1) {
            if ($item->hasChildren()) {
                $classes[] = 'has-sub-cat';
            }
        }

        if ($this->_customhelper->getActionName() == 'catalog_category_view') {
            $cur_cat = $this->_customhelper->getcurrentCategory();
            $categoryPathIds = explode(',', $cur_cat->getPathInStore());
            if (in_array($item->getId(), $categoryPathIds) == '1') {
                $classes[] = 'active';
            }
        }

        return $classes;
    }

    /**
     * Get Custom Links
     */
    public function getCustomLinks($category_id)
    {
        $base_url = rtrim($this->_storeManager->getStore()->getBaseUrl(), '/');
        $customMenus = $this->_customhelper->getConfig('rootmegamenu_option/general/custom_link', $this->_customhelper->getStoreId());
        $customLinkHtml = '';
        if (!empty($customMenus) && $customMenus != '[]') {
            $customMenus = $this->_customhelper->getJsonDecode($customMenus);
            /*
            if ($this->_customhelper->getMagentoVersion() >= '2.2.0') {
                $customMenus = json_decode($customMenus, true);
            } else {
                $customMenus =  \Magento\Framework\Serialize\SerializerInterface::unserialize($customMenus);
            }*/
            if (is_array($customMenus)) {
                foreach ($customMenus as $customMenusRow) {
                    if ($customMenusRow['custommenulink'] != '') {
                        if (substr($customMenusRow['custommenulink'], 0, 1) != '/') {
                            $no_custom_link = $customMenusRow['custommenulink'];
                        } else {
                            $no_custom_link = $base_url.$customMenusRow['custommenulink'];
                        }
                    } else {
                        $no_custom_link = 'javascript:void(0);';
                    }
                    if (isset($customMenusRow['custom_menu_position'])) {
                        if ($customMenusRow['custom_menu_position'] == $category_id && $customMenusRow['custom_menu_position'] != '') {
                            $customLinkHtml .= $this->getCustomDropDownContent($no_custom_link, $customMenusRow);
                            /*
                            $customLinkHtml .= '<li class="custom-menus"><a href="'.$no_custom_link.'">'.$customMenusRow['custommenuname'].'</a>';
                            if ($customMenusRow['custom_menu_block'] != '') {
                                $customLinkHtml .= $this->getLayout()->createBlock('Magento\Cms\Block\Block')->setBlockId($customMenusRow['custom_menu_block'])->toHtml();
                            }
                            $customLinkHtml .= '</li>';
                            */
                        }

                        if ($category_id == false &&
                            ($customMenusRow['custom_menu_position'] == 'default' ||
                             $customMenusRow['custom_menu_position'] == 'right' ||
                             $customMenusRow['custom_menu_position'] == 'left')
                           ) {
                            $customLinkHtml .= $this->getCustomDropDownContent($no_custom_link, $customMenusRow);
                            /*
                            $customLinkHtml .= '<li class="custom-menus"><a href="'.$no_custom_link.'">'.$customMenusRow['custommenuname'].'</a>';
                            if ($customMenusRow['custom_menu_block'] != '') {
                                $customLinkHtml .= $this->getLayout()->createBlock('Magento\Cms\Block\Block')->setBlockId($customMenusRow['custom_menu_block'])->toHtml();
                            }
                            $customLinkHtml .= '</li>';
                            */
                        }
                    }

                }
            }
        }
        return $customLinkHtml;
    }

    protected function getCustomDropDownContent($no_custom_link, $customMenusRow)
    {
        $dropDownHtml = '';
        $dropdownClass = 'custom-menus';
        if ($customMenusRow['custom_menu_block'] != '') {
            $content = $this->getLayout()->createBlock('Magento\Cms\Block\Block')->setBlockId($customMenusRow['custom_menu_block'])->toHtml();
            if (!empty($customMenusRow['custom_menu_layout'])) {
                if ($customMenusRow['custom_menu_layout'] == 4) {
                    $dropDownHtml = '<ul class="rootmenu-submenu">'.$content.'</ul>';
                } elseif ($customMenusRow['custom_menu_layout'] == 5) {
                    $dropDownHtml = '<ul class="rootmenu-submenu dropdown-leftside">'.$content.'</ul>';
                    $dropdownClass .= ' position-relative';
                } elseif ($customMenusRow['custom_menu_layout'] == 2) {
                    $dropDownHtml = '<div class="halfmenu clearfi">'.$content.'</div>';
                } elseif ($customMenusRow['custom_menu_layout'] == 3) {
                    $dropDownHtml = '<div class="halfmenu clearfi dropdown_left">'.$content.'</div>';
                    $dropdownClass .= ' dropdown_left';
                } else {
                    $dropDownHtml = '<div class="megamenu fullmenu clearfix categoriesmenu">'.$content.'</div>';
                }
            } else {
                $dropDownHtml = '<div class="megamenu fullmenu clearfix categoriesmenu">'.$content.'</div>';
            }
        }
        if ($customMenusRow['custom_menu_position'] == 'left' || $customMenusRow['custom_menu_position'] == 'right') {
            $dropdownClass .= ' rwcustomlink-'.$customMenusRow['custom_menu_position'];
        }
        $result = '<li class="'.$dropdownClass.'"><a href="'.$no_custom_link.'">'.$customMenusRow['custommenuname'].'</a>'.$dropDownHtml.'</li>';
        return $result;
    }

    /**
     * Get Social Share Links
     */
    public function getSocialShare()
    {
        $html = '';
        if ($this->_customhelper->getConfig('rootmegamenu_option/general/show_social_share_icon')) {
            $fb = $this->_customhelper->getConfig('rootmegamenu_option/general/fb');
            $twitter = $this->_customhelper->getConfig('rootmegamenu_option/general/twitter');
            $gplus = $this->_customhelper->getConfig('rootmegamenu_option/general/gplus');
            $youtube = $this->_customhelper->getConfig('rootmegamenu_option/general/youtube');
            $vimeo = $this->_customhelper->getConfig('rootmegamenu_option/general/vimeo');
            $instagram = $this->_customhelper->getConfig('rootmegamenu_option/general/instagram');
            $pinterest = $this->_customhelper->getConfig('rootmegamenu_option/general/pinterest');
            $skype = $this->_customhelper->getConfig('rootmegamenu_option/general/skype');
            $emailid = $this->_customhelper->getConfig('rootmegamenu_option/general/emailid');
            $phone = $this->_customhelper->getConfig('rootmegamenu_option/general/phone');

            if ($fb != '') {
                $html .= '<a class="rw-fb" target="_blank" href="'.$fb.'"><span>Facebook</span></a>';
            }
            if ($twitter != '') {
                $html .= '<a class="rw-twitter" target="_blank" href="'.$twitter.'"><span>Twitter</span></a>';
            }
            if ($gplus != '') {
                $html .= '<a class="rw-gplus" target="_blank" href="'.$gplus.'"><span>Google Plus</span></a>';
            }
            if ($youtube != '') {
                $html .= '<a class="rw-youtube" target="_blank" href="'.$youtube.'"><span>Youtube</span></a>';
            }
            if ($vimeo != '') {
                $html .= '<a class="rw-vimeo" target="_blank" href="'.$vimeo.'"><span>Vimeo</span></a>';
            }
            if ($instagram != '') {
                $html .= '<a class="rw-instagram" target="_blank" href="'.$instagram.'"><span>Instagram</span></a>';
            }
            if ($pinterest != '') {
                $html .= '<a class="rw-pinterest" target="_blank" href="'.$pinterest.'"><span>Pinterest</span></a>';
            }
            if ($skype != '') {
                $html .= '<a class="rw-skype" target="_blank" href="skype:'.$skype.'?call"><span>Skype</span></a>';
            }
            if ($emailid != '') {
                $html .= '<a class="rw-emailid" target="_blank" href="mailto:'.$emailid.'"><span>Email</span></a>';
            }
            if ($phone != '') {
                $html .= '<a class="rw-phone" target="_blank" href="tel:'.$phone.'"><span>Phone</span></a>';
            }
        }
        $socialHtml = '';
        if ($html != '') {
            $socialHtml = '<li class="rw-social-links">'.$html.'</li>';
        }
        return $socialHtml;
    }

    protected function masonryCategoryClass($cId)
    {
        $enableMasonry = $this->_customhelper->manageMasonry();
        $masonryClass = '';
        $colClass = 'root-col-';
        if ($enableMasonry == 1) {
            $masonryClass = ' grid';
            $colClass = 'grid-item-';
        } elseif ($enableMasonry == 2) {
            $masonryCategories = $this->_customhelper->masonryCategory();
            if (in_array($cId, $masonryCategories)) {
                $masonryClass = ' grid';
                $colClass = 'grid-item-';
            }
        } else {
            $masonryClass = '';
            $colClass = 'root-col-';
        }
        $msClasses = [$masonryClass, $colClass];
        return $msClasses;
    }

    public function AllCategoryHalfMenu($main_cat, $navCnt0)
    {
        $colnum = 2;
        /*
        $colnum = $main_cat->getMegamenuTypeNumofcolumns();
        if ($colnum == 0) {
            $colnum = 2;
        }
        */
        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        $childrenCategories = $main_cat->getChildrenCategories();
        if (count($childrenCategories)) {
            $catHtml .= '<div class="v_halfmenu varticalmenu_main halfwidth clearfix">';
            $catHtml .= '<div class="root-col-1 clearfix">';
            $catHtml .= '<div class="root-col-' . $colnum . ' clearfix">';
            $catHtml .= '<ul class="level2-popup">';
            $cnt = 1;
            $cat_tot = count($childrenCategories);
            $brk = ceil($cat_tot / $colnum);
            // 2nd Level Category
            $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $navCnt++;
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                $load_cat->getCollection()->addAttributeToFilter('include_in_menu', '1');

                if ($main_cat->getMegamenuShowCatimage() == 1) {
                    if ($this->_customhelper->getMegaMenuImageName($load_cat) != '') {
                        $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat);
                    } else {
                        $imageurl = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                    }
                    $image_html = '<span class="vertical-listing-img"><img ' . $this->getImgWidthHeight($main_cat) . ' src="' . $imageurl . '" alt="' . $load_cat->getName() . '" /></span>';
                } else {
                    $image_html = '<i aria-hidden="true" class="verticalmenu-arrow fa fa-angle-right"></i>';
                }
                /*
                            $catHtml .= '<li class="nav-'.$navCnt0.'-'.$navCnt.' category-item"><a href="'.$load_cat->getURL().'" '.$line_height.'>';
                            $catHtml .= $image_html;
                            $catHtml .= $load_cat->getName().'</a></li>';
                */

                $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . ' category-item"><a href="' . $load_cat->getURL() . '">';
                $catHtml .= $image_html;
                $catHtml .= '<span class="sub-cat-name" style="height:' . $main_cat->getMegamenuShowCatimageWidth() . 'px;">' . $load_cat->getName();
                if ($load_cat->getMegamenuTypeLabeltx() != '') {
                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #' . $load_cat->getMegamenuTypeLabelclr() . '">' . $load_cat->getMegamenuTypeLabeltx() . '</em></span>';
                }
                $catHtml .= '</span>';
                $childrenCategories_2 = $load_cat->getChildrenCategories();
                if (count($childrenCategories_2)) {
                    $catHtml .= '<span class="cat-arrow"></span>';
                }
                $catHtml .= '</a>';
                if (count($childrenCategories_2)) {
                    $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';
                    $catHtml .= '<ul class="level3-popup halfwidth-popup-sub-sub">';
                    $navCnt1 = 0;
                    foreach ($childrenCategories_2 as $childCategory2) {
                        $navCnt1++;
                        $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                        $load_cat_sub->getCollection()->addAttributeToFilter('include_in_menu', '1');
                        if ($main_cat->getMegamenuShowCatimage() == 1) {
                            if ($this->_customhelper->getMegaMenuImageName($load_cat_sub) != '') {
                                $imageurl_sub = $this->_customhelper->getMegaMenuImageUrl($load_cat_sub);
                            } else {
                                $imageurl_sub = $this->getViewFileUrl('Rootways_Megamenu::images/rootimgicon.jpg');
                            }
                            $image_html_sub = '<img style="width:25px; height:25px;" src="' . $imageurl_sub . '" alt="' . $load_cat_sub->getName() . '" />';
                        } else {
                            $image_html_sub = '';
                        }

                        $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . '-' . $navCnt1 . ' category-item"><a class="clearfix" href="' . $load_cat_sub->getURL() . '">';
                        $catHtml .= $image_html_sub;
                        $catHtml .= '<span class="level3-name">' . $load_cat_sub->getName() . '</span>';


                        $childrenCategories_3 = $load_cat_sub->getChildrenCategories();
                        if ($childrenCategories_3) {
                            $catHtml .= '<span class="cat-arrow"></span>';
                        }
                        $catHtml .= '</a>';
                        $catHtml .= $this->levelFourCategoriesWithImageHtml($main_cat, $childrenCategories_3, $navCnt0, $navCnt, $navCnt1);
                        $catHtml .= '</li>';
                    }
                    $catHtml .= '</ul>';
                }

                $catHtml .= '</li>';
                if ($cnt % $brk == 0 && $cnt != $cat_tot) {
                    $catHtml .= '</ul></div><div class="root-col-' . $colnum . ' clearfix"><ul class="level2-popup">';
                }
                $cnt++;
            }
            $catHtml .= '</ul>';
            $catHtml .= '</div>';
            $catHtml .= '</div>';
            $catHtml .= '</div>';
        }
        return $catHtml;
    }

    public function AllCategoryHalfTitleMenu($main_cat, $navCnt0)
    {
        $viewMoreAfter = $main_cat->getMegamenuTypeViewmore();
        $colnum = $this->_customhelper->getConfig('rootmegamenu_option/general/show_all_columns');
        $widthClass = $this->_customhelper->getConfig('rootmegamenu_option/general/all_category_layout') == '2' ?
            'v_halfmenu varticalmenu_main halfwidth clearfix' :
            'vertical_fullwidthmenu varticalmenu_main fullwidth clearfix';

        $showIconLevl2 = $this->_customhelper->getConfig('rootmegamenu_option/general/show_all_level2_icon');
        $showIcon = $this->_customhelper->getConfig('rootmegamenu_option/general/display_sub_cat_icon');
        $media_url = $this->_customhelper->getMediaUrl();

        $left_width = $main_cat->getMegamenuTypeLeftblockW();
        $right_width = $main_cat->getMegamenuTypeRightblockW();
        $cat_width = 12 - ($left_width + $right_width);
        $category_area_width = 'root-sub-col-'.$cat_width;
        $left_content_area = 'root-sub-col-'.$left_width;
        $right_content_area = 'root-sub-col-'.$right_width;
        $catHtml = '';
        $childrenCategories = $main_cat->getChildrenCategories();
        if (count($childrenCategories)) {
            $catHtml .= '<div class="' . $widthClass . '">';
            $catHtml .= '<div class="root-col-1 clearfix grid">';
            // 2nd Level Category
            $navCnt = 0;
            foreach ($childrenCategories as $childCategory) {
                $catHtml .= '<div class="grid-item-' . $colnum . ' clearfix">';
                $navCnt++;
                $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());

                $image_html = '';
                if ($showIconLevl2 == 1 && $this->_customhelper->getMegaMenuImageName($load_cat) != '') {
                    $imageurl = $this->_customhelper->getMegaMenuImageUrl($load_cat);
                    $image_html = '<a href="' . $load_cat->getURL() . '" class="catproductimg"><img ' . $this->getImgWidthHeight($main_cat) . ' src="' . $imageurl . '" alt="' . $load_cat->getName() . '" /></a>';
                }
                //$catHtml .= $this->getImageHtmlAsTitle($main_cat, $load_cat, 1);
                $catHtml .= $image_html;

                $catHtml .= '<div class="title_normal"><a href="' . $load_cat->getURL() . '">' . $load_cat->getName() . '</a>';
                if ($load_cat->getMegamenuTypeLabeltx() != '') {
                    $catHtml .= '<span class="top-sub-label"><em class="rootmenutag" style="background-color: #' . $load_cat->getMegamenuTypeLabelclr() . '">' . $load_cat->getMegamenuTypeLabeltx() . '</em></span>';
                }
                $catHtml .= '</div>';
                $childrenCategories_2 = $load_cat->getChildrenCategories();
                if (count($childrenCategories_2)) {
                    $catHtml .= '<span class="vertical-click"><i class="verticalmenu-arrow fa fa-angle-down" aria-hidden="true"></i></span>';
                    $catHtml .= '<ul class="level3-popup halfwidth-popup-sub-sub">';
                    $navCnt1 = 0;
                    foreach ($childrenCategories_2 as $childCategory2) {
                        if ($navCnt1 >= $viewMoreAfter && $viewMoreAfter != '') {
                            $catHtml .= '<li><a class="view-more" href="' . $load_cat->getURL() . '">' . __('View More') . '</a></li>';
                            break;
                        }
                        $navCnt1++;
                        $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                        if ($showIcon == 1) {
                            $image_html_sub = '<span class="rw_allsubcat_arrow"></span>';
                        } elseif ($showIcon == 2) {
                            $subCatIcon = $this->_customhelper->getConfig('rootmegamenu_option/general/sub_category_icon');
                            if ($subCatIcon != '') {
                                $image_html_sub = '<img class="rw_allsubcat_icon" style="width:25px; height:25px;" src="' . $media_url . 'rootways/images/' . $subCatIcon . '" alt="' . $load_cat_sub->getName() . '" />';
                            }
                        } else {
                            $image_html_sub = '';
                        }
                        $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . '-' . $navCnt1 . ' category-item"><a class="clearfix" href="' . $load_cat_sub->getURL() . '">';
                        $catHtml .= $image_html_sub;
                        $catHtml .= '<span class="level3-name">' . $load_cat_sub->getName() . '</span>';

                        $childrenCategories_3 = $load_cat_sub->getChildrenCategories();
                        if (count($childrenCategories_3)) {
                            $catHtml .= '<span class="cat-arrow"></span>';
                        }
                        $catHtml .= '</a>';
                        $catHtml .= $this->levelFourCategoriesWithImageHtml($main_cat, $childrenCategories_3, $navCnt0, $navCnt, $navCnt1);
                        $catHtml .= '</li>';
                    }
                    $catHtml .= '</ul>';
                }
                $catHtml .= '</div>';
            }
            $catHtml .= '</div>';
            $catHtml .= '</div>';
        }
        return $catHtml;
    }

    public function allCategoriesUlHtml($totalCategory, $htmlType = "fullwidth")
    {
        $ulClassName = $htmlType == 'sidebyside' ? 'menu multitab-slide multitab-lvl1' : 'vertical-list clearfix';
        $ulTagHtml = '<ul class="'.$ulClassName.'">';
        $main_all_cat_cnt = (int)$totalCategory;
        $viewAllCat = '';
        if ($this->_customhelper->getConfig('rootmegamenu_option/general/show_all_category') == 2) {
            $selectedCategoriesArray = '';
            $selectedCategories = $this->_customhelper->getConfig('rootmegamenu_option/general/show_all_category_selected_categories');
            if ($selectedCategories != '') {
                $viewAllCat = explode(',', $selectedCategories);
            }
        } else {
            $viewAllCategory = $this->_customhelper->getCategory($this->_customhelper->getRootCategoryId());
            $viewAllCat = $viewAllCategory->getChildrenCategories();
        }
        foreach ($viewAllCat as $category) {
            $main_all_cat_cnt++;
            $all_cat_category_load = gettype($category) == 'object' ? $this->_categoryFactory->create()->load($category->getId()) : $this->_categoryFactory->create()->load($category);

            if ($htmlType == 'sidebyside') {
                $ulTagHtml .= $this->allCategoriesSideBySideLiHtml($htmlType, $main_all_cat_cnt, $all_cat_category_load);
            } else {
                $ulTagHtml .= $this->allCategoriesLiHtml($htmlType, $main_all_cat_cnt, $all_cat_category_load);
            }
        }
        $ulTagHtml .= '</ul>';

        return $ulTagHtml;
    }

    public function allCategoriesLiHtml($htmlType, $main_all_cat_cnt, $all_cat_category_load)
    {
        $custom_cls_all_cat = 'nav-' . $main_all_cat_cnt . ' category-item';
        $liTagHtml = '<li class="rootverticalnav ' . $custom_cls_all_cat . '">
        <a href="' . $all_cat_category_load->getURL() . '">';
        if ($this->_customhelper->getConfig('rootmegamenu_option/general/show_all_category_icon') == 1 &&
            $all_cat_category_load->getImageUrl() != ''
        ) {
            $liTagHtml .= '<span class="main-category-name">
                <i class="main-category-icon"><img src="' . $all_cat_category_load->getImageUrl() . '" alt="' . $all_cat_category_load->getName() . '"/></i>
                <em>' . $all_cat_category_load->getName() . '</em>
            </span>';
        } else {
            $liTagHtml .= $all_cat_category_load->getName();
        }
        $liTagHtml .= '</a>';
        //$liTagHtml .= $this->AllCategoryHalfMenu($all_cat_category_load, $main_all_cat_cnt);
        $liTagHtml .= $this->AllCategoryHalfTitleMenu($all_cat_category_load, $main_all_cat_cnt);
        $liTagHtml .= '</li>';

        return $liTagHtml;
    }

    public function allCategoriesSideBySideLiHtml($htmlType, $main_all_cat_cnt, $all_cat_category_load)
    {
        $childrenCategories = $all_cat_category_load->getChildrenCategories();
        $liClassLevel0 = count($childrenCategories) ? 'menu-link link-with-subcat' : 'menu-link';
        $liTagHtml = '<li class="nav-' . $main_all_cat_cnt . ' category-item">
        <a class="'.$liClassLevel0.'" aria-haspopup="true" href="' . $all_cat_category_load->getURL() . '">';
        if ($this->_customhelper->getConfig('rootmegamenu_option/general/show_all_category_icon') == 1 &&
            $all_cat_category_load->getImageUrl() != ''
        ) {
            $liTagHtml .= '<span class="main-category-name">
                <i class="main-category-icon"><img src="' . $all_cat_category_load->getImageUrl() . '" alt="' . $all_cat_category_load->getName() . '"/></i>
                <em>' . $all_cat_category_load->getName() . '</em>
            </span>';
        } else {
            $liTagHtml .= $all_cat_category_load->getName();
        }
        $liTagHtml .= '</a>';
        $liTagHtml .= $this->allCategoriesSideBySideSubUlHtml($all_cat_category_load, $childrenCategories, $main_all_cat_cnt);
        $liTagHtml .= '</li>';

        return $liTagHtml;
    }

    public function allCategoriesSideBySideSubUlHtml($main_cat, $childrenCategories, $navCnt0)
    {
        $catHtml = '';
        if (count($childrenCategories)) {
            $catHtml .= '<span class="vertical-click"><i class="verticalmenu-arrow fa fa-angle-down" aria-hidden="true"></i></span>';
            $catHtml .= '<ul class="menu multitab-lvl2">';
                $navCnt = 0;
                foreach ($childrenCategories as $childCategory) {
                    $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . ' category-item">';
                    $navCnt++;

                    $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
                    $childrenCategories_2 = $load_cat->getChildrenCategories();
                    $liClassLevel1 = count($childrenCategories_2) ? 'menu-link link-with-subcat' : 'menu-link';
                    $catHtml .= '<a href="'.$load_cat->getURL().'" class="'.$liClassLevel1.'" aria-haspopup="true">'.$load_cat->getName().'</a>';
                    $catHtml .= '<span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>';

                    if (count($childrenCategories_2)) {
                        $catHtml .= '<span class="vertical-click"><i class="verticalmenu-arrow fa fa-angle-down" aria-hidden="true"></i></span>';
                        $catHtml .= '<ul class="menu multitab-lvl3">';
                        $navCnt1 = 0;
                        foreach ($childrenCategories_2 as $childCategory2) {
                            $navCnt1++;
                            $load_cat_sub = $this->categoryRepository->get($childCategory2->getId(), $this->_customhelper->getStoreId());
                            $childrenCategories_3 = $load_cat_sub->getChildrenCategories();
                            $liClassLevel2 = count($childrenCategories_3) ? 'menu-link link-with-subcat' : 'menu-link';
                            $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . '-' . $navCnt1 . ' category-item"><a href="'.$load_cat_sub->getURL().'" class="'.$liClassLevel2.'" aria-haspopup="true">'.$load_cat_sub->getName().'</a>
                                <span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>
                            </a>';

                            if (count($childrenCategories_3)) {
                                $catHtml .= '<span class="vertical-click"><i class="verticalmenu-arrow fa fa-angle-down" aria-hidden="true"></i></span>';
                                $catHtml .= '<ul class="menu multitab-lvl4">';
                                $navCnt2 = 0;
                                foreach ($childrenCategories_3 as $childCategory3) {
                                    $navCnt2++;
                                    $load_cat_sub_sub = $this->categoryRepository->get($childCategory3->getId(), $this->_customhelper->getStoreId());
                                    $catHtml .= '<li class="nav-' . $navCnt0 . '-' . $navCnt . '-' . $navCnt1 . '-' . $navCnt2 .' category-item"><a href="'.$load_cat_sub_sub->getURL().'" class="menu-link" aria-haspopup="true">'.$load_cat_sub_sub->getName().'</a>
                                        <span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>
                                    </a></li>';
                                }
                                $catHtml .= '</ul>';
                            }
                            $catHtml .= '</li>';
                        }
                        $catHtml .= '</ul>';
                    }
                    $catHtml .= '</li>';
                }
            $catHtml .= '</ul>';
        }

        return $catHtml;
    }

    public function viewAllCategoriesHTML($totalCategory)
    {
        $htmlType = $this->_customhelper->getConfig('rootmegamenu_option/general/all_category_layout') == '3' ? 'sidebyside' : 'fullwidth';
        $catHtml = '';
        if ($this->_customhelper->getConfig('rootmegamenu_option/general/show_all_category') == 1 ||
            $this->_customhelper->getConfig('rootmegamenu_option/general/show_all_category') == 2
        ) {
            $catHtml .= '
            <li class="rw-vertical-menu all-category-wrapper">
                <span class="rootmenu-click"><i class="rootmenu-arrow"></i></span>
                <a class="has-sub-cat" href="javaScript:void(0);"
                title="'.$this->_customhelper->getConfig('rootmegamenu_option/general/all_category_title').'"><span></span>'.$this->_customhelper->getConfig('rootmegamenu_option/general/all_category_title').'
                </a>';
                $allCategoryClassName = $htmlType != 'sidebyside' ? "verticalmenu02 clearfix" : "tabbing-sidebyside";
                $catHtml .= '<div class="'.$allCategoryClassName.'">';
                    $catHtml .= $this->allCategoriesUlHtml($totalCategory, $htmlType);
                $catHtml .= '</div>';
            $catHtml .= '</li>';
        }
        return $catHtml;
    }

    /**
     * @param $cat
     * @return string
     */
    public function getLineHeight($cat)
    {
        return !empty($cat->getMegamenuShowCatimageHeight()) ?
            'style="line-height:'.$cat->getMegamenuShowCatimageHeight().'px;"' :
            '';

    }

    /**
     * @param $cat
     * @return string
     */
    public function getHeight($cat)
    {
        return !empty($cat->getMegamenuShowCatimageHeight()) ?
            'style="height:'.$cat->getMegamenuShowCatimageHeight().'px;"' :
            '';
    }

    /**
     * @param $cat
     * @return string
     */
    public function getImgWidthHeight($cat)
    {
        return !empty($cat->getMegamenuShowCatimageWidth()) && !empty($cat->getMegamenuShowCatimageHeight()) ?
            'style="width:'.$cat->getMegamenuShowCatimageWidth().'px; height:'.$cat->getMegamenuShowCatimageHeight().'px;"' :
            '';
    }
}
