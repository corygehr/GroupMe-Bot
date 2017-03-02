<?php
    /**
     * Sunshines/html/add.tpl
     * Contains the HTML template for the add subsection
	*
     * @author Cory Gehr
     */
?>
<h1>Hi, <?php echo $_SESSION['USER_NAME']; ?>!</h1>
<p>
	Use the form below to write an anonymous sunshine.
</p>
<?php
     if($this->get('message'))
     {
?>
<p>
     <p>
          <b><?php echo $this->get('message'); ?></b>
     </p>
</p>
<?php
     }
?>
<form method="post">
     <fieldset>
          <legend>Sunshine</legend>
          <p>
               <label for="group">Group Name</label><br>
               <select id="group" name="group">
                    <option>Select a Group</option>
<?php
     $groups = $this->get('groups');

     foreach($groups as $id => $name)
     {
?>
                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
<?php
     }
?>
               </select>
          </p>
          <p>
               <label for="message">Your Message</label><br>
               <textarea id="message" name="message"></textarea>
          </p>
          <p>
               <label for="when">When should the sunshine appear?</label><br>
               <select id="when" name="when">
                    <option>Select an Option</option>
                    <option value="later">Later</option>
                    <option value="now">Now</option>
               </select>
          </p>
          <p>
               Selecting 'Later' means your sunshine may appear 
               at 12:00PM (EST) in the selected group (if it is chosen). 
               'Now' will push it to the group immediately. Three sunshines 
               will be displayed one minute apart.
          </p>
          <input type="submit" value="Submit" />
     </fieldset>
</form>