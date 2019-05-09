<?php
// $HeadURL: https://joomgallery.org/svn/joomgallery/JG-2.0/Plugins/JoomAlphaUserPoints/trunk/joomalphauserpoints.php $
// $Id: joomalphauserpoints.php 3823 2012-07-10 21:38:47Z chraneco $
/******************************************************************************\
**   JoomGallery Plugin 'JoomAlphaUserPoints' 2.0                             **
**   By: JoomGallery::ProjectTeam                                             **
**   Copyright (C) 2009 - 2012  Patrick (Chraneco)                            **
**   Released under GNU GPL Public License                                    **
**   License: http://www.gnu.org/copyleft/gpl.html                            **
\******************************************************************************/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * JoomGallery Plugin 'JoomAlphaUserPoints'
 *
 * @package     Joomla
 * @subpackage  JoomGallery
 * @since       1.5
 */
class plgJoomGalleryJoomAlphaUserPoints extends JPlugin
{
  /**
   * Constructor
   *
   * For php4 compatability we must not use the __constructor as a constructor for plugins
   * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
   * This causes problems with cross-referencing necessary for the observer design pattern.
   *
   * @access  protected
   * @param   object    $subject  The object to observe
   * @param   object    $params   The object that holds the plugin parameters
   * @return  void
   * @since   1.5
   */
  public function plgJoomGalleryJoomAlphaUserPoints(&$subject, $params)
  {
    parent::__construct($subject, $params);

    $this->loadLanguage();

    $file = JPATH_ROOT.'/components/com_alphauserpoints/helper.php';
    if(file_exists($file))
    {
      require_once($file);
    }
    else
    {
      JError::raiseError(500, JText::_('PLG_JOOMALPHAUSERPOINTS_ALPHAUSERPOINTS_SEEMS_NOT_TO_BE_INSTALLED'));
    }
  }

  /**
   * onJoomAfterComment method
   *
   * Method is called after a comment was successfully saved
   *
   * @access  public
   * @param   object  $comment  Holds the successfully saved comment information
   * @return  void
   * @since   1.5
   */
  public function onJoomAfterComment($comment)
  {
    if(!$this->params->get('points_for_comment_on_own_image'))
    {
      $db = JFactory::getDbo();
      $db->setQuery($db->getQuery(true)
                       ->select('owner')
                       ->from($db->qn('#__joomgallery'))
                       ->where('id = '.(int)$comment->cmtpic));
      if($db->loadResult() == $comment->userid)
      {
        return;
      }
    }

    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_comment', '',  '', JText::_('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_COMMENTING'));
  }

  /**
   * onJoomAfterTag method
   *
   * Method is called after a mail was successfully sent with the send2friend function
   *
   * @access  public
   * @param   object  $mail Holds the successfully sent mail information
   * @return  void
   * @since   1.5
   */
  public function onJoomAfterSend2Friend($mail)
  {
    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_send2friend', '',  '', JText::_('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_SEND2FRIEND'));
  }

  /**
   * onJoomAfterTag method
   *
   * Method is called after a user was successfully tagged in an image
   *
   * @access  public
   * @param   object  $tag  Holds the successfully saved tag information
   * @return  void
   * @since   1.5
   */
  public function onJoomAfterTag($tag)
  {
    if(!$this->params->get('points_for_ones_own_tag') && $tag->by == $tag->nuserid)
    {
      return;
    }

    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_tag', '',  '', JText::_('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_TAGGING'));
  }

  /**
   * onJoomAfterVote method
   *
   * Method is called after a vote was successfully saved
   *
   * @access  public
   * @param   object  $row  Holds the successfully saved vote information
   * @param   int     $vote Holds the number of the voting
   * @return  void
   * @since   1.5
   */
  public function onJoomAfterVote($row, $vote)
  {
    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_vote', '',  '', JText::_('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_VOTING'));
  }

  /**
   * onJoomBeforeDownload method
   *
   * Method is called before starting to download an image
   *
   * @access  public
   * @param   object  $image              The data of the image to download
   * @param   string  $img                The absolute path of the image to download
   * @param   string  $type               The image size to download (one of 'thumb', 'img' and 'orig')
   * @param   boolean $include_watermark  Determines whether a watermark will be included into the image
   * @return  boolean False will stop the download from being performed
   * @since   1.5
   */
  public function onJoomBeforeDownload(&$image, &$img, &$type, &$include_watermark)
  {
    $user       = JFactory::getUser();

    $referreid  = AlphaUserPointsHelper::getAnyUserReferreID($user->get('id'));

    $numpoints  = AlphaUserPointsHelper::getPointsRule('plgaup_joomgallery_download');

    if($referreid && !AlphaUserPointsHelper::operationIsFeasible($referreid, $numpoints))
    {
      $msg = JText::_('AUP_YOUDONOTHAVEENOUGHPOINTSTOPERFORMTHISOPERATION');
      JFactory::getApplication()->enqueueMessage($msg, 'notice');

      return false;
    }

    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_download', '',  '', JText::_('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_DOWNLOADING'));

    return true;
  }

  /**
   * onJoomBeforeZipDownload method
   *
   * Method is called before starting to download a zip archive
   *
   * @access  public
   * @param   array   $files  An array of absolute paths of the images which will be included in the zip
   * @return  boolean False will stop the download from being performed
   * @since   1.5
   */
  public function onJoomBeforeZipDownload(&$files)
  {
    $user       = JFactory::getUser();

    $referreid  = AlphaUserPointsHelper::getAnyUserReferreID($user->get('id'));

    $numpoints  = AlphaUserPointsHelper::getPointsRule('plgaup_joomgallery_download');

    $count      = count($files);

    if($referreid && !AlphaUserPointsHelper::operationIsFeasible($referreid, $numpoints * $count))
    {
      $this->loadLanguage('com_alphauserpoints', JPATH_SITE);
      $msg = JText::_('AUP_YOUDONOTHAVEENOUGHPOINTSTOPERFORMTHISOPERATION');
      JFactory::getApplication()->enqueueMessage($msg, 'notice');

      return false;
    }

    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_download', '',  '', JText::sprintf('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_ZIPDOWNLOAD', $count), $numpoints * $count);

    return true;
  }

  /**
   * onJoomBeforeUpload method
   *
   * Method is called before starting to upload an image
   *
   * @access  public
   * @return  boolean False will stop the upload from being performed
   * @since   1.5
   */
  public function onJoomBeforeUpload()
  {
    $user       = JFactory::getUser();

    $referreid  = AlphaUserPointsHelper::getAnyUserReferreID($user->get('id'));

    $numpoints  = AlphaUserPointsHelper::getPointsRule('plgaup_joomgallery_upload');

    if($referreid && !AlphaUserPointsHelper::operationIsFeasible($referreid, $numpoints))
    {
      $this->loadLanguage('com_alphauserpoints', JPATH_SITE);
      $msg = JText::_('AUP_YOUDONOTHAVEENOUGHPOINTSTOPERFORMTHISOPERATION');
      JFactory::getApplication()->enqueueMessage($msg, 'notice');

      return false;
    }

    return true;
  }

  /**
   * onJoomAfterUpload method
   *
   * Method is called after an image was successfully uploaded and saved
   *
   * @access  public
   * @return  void
   * @since   1.5
   */
  public function onJoomAfterUpload()
  {
    AlphaUserPointsHelper::newpoints('plgaup_joomgallery_upload', '',  '', JText::_('PLG_JOOMALPHAUSERPOINTS_POINTS_FOR_UPLOADING'));
  }
}