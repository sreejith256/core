<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

//Get alternative header names
$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {	
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
	if ($gibbonCourseClassID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
	
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/internalAssessment_manage.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>" . _('Manage') . " " . $row["course"] . "." . $row["class"] . " " . _('Internal Assessments') . "</a> > </div><div class='trailEnd'>" . _('Add Multiple Columns') . "</div>" ;
			print "</div>" ;

			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail5") {
					$addReturnMessage=_("Your request failed due to an attachment error.") ;	
				}
				else if ($addReturn=="fail6") {
					$addReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
				}
				else if ($addReturn=="success0") {
					$addReturnMessage=_("Your request was completed successfully. You can now add another record if you wish.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			?>

			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/internalAssessment_manage_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Class') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="right">
							<?php
							print "<select multiple name='gibbonCourseClassIDMulti[]' id='gibbonCourseClassIDMulti[]' style='width:300px; height:150px'>" ;
								//LIST BY YEAR GROUP!
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonYearGroup.name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonYearGroup ON (gibbonCourse.gibbonYearGroupIDList LIKE concat( '%', gibbonYearGroup.gibbonYearGroupID, '%' )) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonYearGroup.sequenceNumber, course, class" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								$lastName="" ;
								while ($rowSelect=$resultSelect->fetch()) {
									//Set opt groups
									if ($lastName=="" OR $lastName!=$rowSelect["name"]) {
										print "<optgroup label='--" . $rowSelect["name"] . "--'/>" ;
									}
									$lastName=$rowSelect["name"] ;
									print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
								}		
							print "</select>" ;
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=20 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Description') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="description" id="description" maxlength=1000 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var description=new LiveValidation('description');
								description.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<?php
					$types=getSettingByScope($connection2, "Formal Assessment", "internalAssessmentTypes") ;
					if ($types!=FALSE) {
						$types=explode(",", $types) ;
						?>
						<tr>
							<td> 
								<b><?php print _('Type') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="type" id="type" style="width: 302px">
									<option value="Please select..."><?php print _('Please select...') ?></option>
									<?php
									for ($i=0; $i<count($types); $i++) {
										?>
										<option value="<?php print trim($types[$i]) ?>"><?php print trim($types[$i]) ?></option>
									<?php
									}
									?>
								</select>
								<script type="text/javascript">
									var type=new LiveValidation('type');
									type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								 </script>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print _('Attachment') ?></b><br/>
						</td>
						<td class="right">
							<input type="file" name="file" id="file"><br/><br/>
							<?php
							
							//Get list of acceptable file extensions
							try {
								$dataExt=array(); 
								$sqlExt="SELECT * FROM gibbonFileExtension" ;
								$resultExt=$connection2->prepare($sqlExt);
								$resultExt->execute($dataExt);
							}
							catch(PDOException $e) { }
							$ext="" ;
							while ($rowExt=$resultExt->fetch()) {
								$ext=$ext . "'." . $rowExt["extension"] . "'," ;
							}
							?>
				
							<script type="text/javascript">
								var file=new LiveValidation('file');
								file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2> 
							<h3>
								<?php print _("Assessment")  ?>
							</h3>
						</td>
					</tr>
					<script type="text/javascript">
						/* Homework Control */
						$(document).ready(function(){
							 $(".attainment").click(function(){
								if ($('input[name=attainment]:checked').val()=="Y" ) {
									$("#gibbonScaleIDAttainmentRow").slideDown("fast", $("#gibbonScaleIDAttainmentRow").css("display","table-row")); 
								} else {
									$("#gibbonScaleIDAttainmentRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td> 
							<b><?php if ($attainmentAlternativeName!="") { print sprintf(_('Assess %1$s?'), $attainmentAlternativeName) ; } else { print _('Assess Attainment?') ; } ?> *</b><br/>
						</td>
						<td class="right">
							<input checked type="radio" name="attainment" value="Y" class="attainment" /> <?php print _('Yes') ?>
							<input type="radio" name="attainment" value="N" class="attainment" /> <?php print _('No') ?>
						</td>
					</tr>
					<tr id="gibbonScaleIDAttainmentRow">
						<td> 
							<b><?php if ($attainmentAlternativeName!="") { print $attainmentAlternativeName . " " . _('Scale') ; } else { print _('Attainment Scale') ; } ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gibbonScaleIDAttainment" id="gibbonScaleIDAttainment" style="width: 302px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonScaleID"]==$_SESSION[$guid]["primaryAssessmentScale"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(_($rowSelect["name"])) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Include Comment?') ?> *</b><br/>
						</td>
						<td class="right">
							<input checked type="radio" name="comment" value="Y" class="comment" /> <?php print _('Yes') ?>
							<input type="radio" name="comment" value="N" class="comment" /> <?php print _('No') ?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Include Uploaded Response?') ?> *</b><br/>
						</td>
						<td class="right">
							<input checked type="radio" name="uploadedResponse" value="Y" class="uploadedResponse" /> <?php print _('Yes') ?>
							<input type="radio" name="uploadedResponse" value="N" class="uploadedResponse" /> <?php print _('No') ?>
						</td>
					</tr>
					
		
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Access') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Viewable to Students') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="viewableStudents" id="viewableStudents" style="width: 302px">
								<option value="Y"><?php print _('Yes') ?></option>
								<option value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Viewable to Parents') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="viewableParents" id="viewableParents" style="width: 302px">
								<option value="Y"><?php print _('Yes') ?></option>
								<option value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Go Live Date') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('1. Format') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('2. Column is hidden until date is reached.') ?></i></span>
						</td>
						<td class="right">
							<input name="completeDate" id="completeDate" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var completeDate=new LiveValidation('completeDate');
								completeDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#completeDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?><br/>
							<?php print getMaxUpload() ; ?>
							</i></span>
						</td>
						<td class="right">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}	
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
}
?>