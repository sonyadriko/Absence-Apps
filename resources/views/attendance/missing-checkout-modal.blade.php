<!-- Missing Checkout Correction Modal -->
<div class="modal fade" id="missingCheckoutModal" tabindex="-1" aria-labelledby="missingCheckoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="missingCheckoutModalLabel">Complete Missing Checkout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="missingCheckoutForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i>
                        <span id="missingCheckoutInfo">You have a pending checkout that needs to be completed.</span>
                    </div>
                    
                    <input type="hidden" id="attendanceId" name="attendance_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="checkoutDate" class="form-label">Checkout Date</label>
                            <input type="date" class="form-control" id="checkoutDate" name="actual_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="checkoutTime" class="form-label">Checkout Time</label>
                            <input type="time" class="form-control" id="checkoutTime" name="check_out_time" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Missing Checkout</label>
                        <select class="form-select" id="reasonSelect" onchange="toggleCustomReason()">
                            <option value="">Select a reason...</option>
                            <option value="forgot">Forgot to checkout</option>
                            <option value="overtime">Working overtime past midnight</option>
                            <option value="emergency">Emergency situation</option>
                            <option value="technical">Technical issues with the system</option>
                            <option value="custom">Other (please specify)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customReasonDiv" style="display: none;">
                        <label for="customReason" class="form-label">Please specify reason</label>
                        <textarea class="form-control" id="customReason" name="reason" rows="3" maxlength="500"></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small><i class="fas fa-exclamation-triangle"></strong> Note: False information may result in disciplinary action.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitMissingCheckout">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Submit Correction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Function to show missing checkout modal
function showMissingCheckoutModal(attendanceData) {
    const modal = new bootstrap.Modal(document.getElementById('missingCheckoutModal'));
    
    // Set attendance ID
    document.getElementById('attendanceId').value = attendanceData.id;
    
    // Set info message
    const infoText = `You checked in on ${attendanceData.date} at ${attendanceData.check_in_time} but did not check out.`;
    document.getElementById('missingCheckoutInfo').textContent = infoText;
    
    // Set default date (next day if overtime)
    const attendanceDate = new Date(attendanceData.date);
    const nextDay = new Date(attendanceDate);
    nextDay.setDate(nextDay.getDate() + 1);
    
    // Set max date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('checkoutDate').max = today;
    document.getElementById('checkoutDate').min = attendanceData.date;
    document.getElementById('checkoutDate').value = attendanceData.date;
    
    modal.show();
}

// Toggle custom reason field
function toggleCustomReason() {
    const reasonSelect = document.getElementById('reasonSelect');
    const customReasonDiv = document.getElementById('customReasonDiv');
    const customReason = document.getElementById('customReason');
    
    if (reasonSelect.value === 'custom') {
        customReasonDiv.style.display = 'block';
        customReason.required = true;
    } else {
        customReasonDiv.style.display = 'none';
        customReason.required = false;
        customReason.value = '';
    }
}

// Handle form submission
document.getElementById('missingCheckoutForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitMissingCheckout');
    const spinner = submitBtn.querySelector('.spinner-border');
    const reasonSelect = document.getElementById('reasonSelect');
    const customReason = document.getElementById('customReason');
    
    // Get the reason
    let reason = '';
    if (reasonSelect.value === 'custom') {
        reason = customReason.value;
    } else {
        reason = reasonSelect.options[reasonSelect.selectedIndex].text;
    }
    
    if (!reason || reasonSelect.value === '') {
        showAlert('error', 'Please select or specify a reason');
        return;
    }
    
    // Disable submit button and show spinner
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    try {
        const formData = new FormData(e.target);
        formData.set('reason', reason);
        
        const response = await fetch('/api/employee/attendance/submit-missing-checkout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message || 'Missing checkout corrected successfully');
            bootstrap.Modal.getInstance(document.getElementById('missingCheckoutModal')).hide();
            
            // Refresh attendance status
            if (window.refreshAttendanceStatus) {
                window.refreshAttendanceStatus();
            }
            
            // Reset form
            e.target.reset();
            toggleCustomReason();
        } else {
            showAlert('error', result.message || 'Failed to submit correction');
        }
    } catch (error) {
        console.error('Error submitting correction:', error);
        showAlert('error', 'An error occurred. Please try again.');
    } finally {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
    }
});

// Check for missing checkouts on page load
async function checkMissingCheckouts() {
    try {
        const response = await fetch('/api/employee/attendance/missing-checkouts', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            // Show notification for missing checkouts
            const latestMissing = result.data[0];
            
            if (window.showNotification) {
                window.showNotification('warning', 
                    `You have a missing checkout from ${latestMissing.date}. Please complete it.`,
                    {
                        persistent: true,
                        action: {
                            text: 'Complete Now',
                            callback: () => showMissingCheckoutModal(latestMissing)
                        }
                    }
                );
            }
        }
    } catch (error) {
        console.error('Error checking missing checkouts:', error);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check for missing checkouts after a short delay
    setTimeout(checkMissingCheckouts, 2000);
});
</script>
