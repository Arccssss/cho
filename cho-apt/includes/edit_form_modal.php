<div id="editFormModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Consent Form</h3>
            <button class="close-btn" onclick="closeEditForm()">&times;</button>
        </div>
        <div class="modal-body">
            <?php if ($edit_error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $edit_error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_form">
                <input type="hidden" name="form_id" id="editFormId">
                
                <div class="form-group">
                    <label for="editPatientName">Patient Name</label>
                    <input type="text" id="editPatientName" name="patient_name" required>
                </div>
                
                <div class="form-group">
                    <label for="editServiceType">Service Type</label>
                    <input type="text" id="editServiceType" name="service_type" required>
                </div>
                
                <div class="form-group">
                    <label for="editFormDate">Form Date</label>
                    <input type="date" id="editFormDate" name="form_date" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Form
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($editing_form) && $editing_form): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('editFormModal').style.display = 'flex';
        document.getElementById('editFormId').value = '<?php echo $editing_form['id']; ?>';
        document.getElementById('editPatientName').value = '<?php echo htmlspecialchars($editing_form['patient_name']); ?>';
        document.getElementById('editServiceType').value = '<?php echo htmlspecialchars($editing_form['service_type']); ?>';
        document.getElementById('editFormDate').value = '<?php echo $editing_form['form_date']; ?>';
    });
</script>
<?php endif; ?>