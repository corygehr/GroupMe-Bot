<?php
    /**
     * Sunshines/html/add.tpl
     * Contains the HTML template for the add subsection
	*
     * @author Cory Gehr
     */
?>
<h1>Sunshines!</h1>
<p>
	Use the form below to write an anonymous sunshine.
</p>
<?php
     if($this->get('message'))
     {
?>
<p>
     <p>
          <?php echo $this->get('message'); ?>
     </p>
</p>
<?php
     }
?>
<form method="post">
     <select id="group" name="group">
          <option>Select a Group</option>
     </select>
     <textarea id="message" name="message"></textarea>
     <select id="when" name="when">
          <option value="later">Later</option>
          <option value="now">Now</option>
     </select>
     <p>
          Selecting 'Later' means your sunshine may appear 
          at 12PM (EST) in the selected group (if it is chosen). 
          'Now' will push it to the group immediately.
     </p>
     <input type="submit" value="Submit" />
</form>