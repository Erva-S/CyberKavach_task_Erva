<div class="page-shell">
    <header class="section-head">
        <div>
            <div class="eyebrow">Event Management</div>
            <h2>Create New Event</h2>
            <p>Draft a new event. It will require Student Coordinator approval before publishing.</p>
        </div>
    </header>

    <form method="POST" action="/events/create" enctype="multipart/form-data" class="card" style="max-width: 800px; margin: 0 auto;">
        
        <?= \CyberKavach\Core\View::csrfInput() ?>

        <div class="field-grid">
            <h3>Basic Details</h3>
            
            <div class="field">
                <label for="title">Event Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" required placeholder="e.g., Intro to Penetration Testing">
            </div>

            <div class="field">
                <label for="category_id">Event Category</label>
                <select id="category_id" name="category_id">
                    <option value="">Select a category...</option>
                    <option value="1">Workshop</option>
                    <option value="2">Seminar</option>
                    <option value="3">CTF Competition</option>
                </select>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="What will participants learn?"></textarea>
            </div>

            <div class="field">
                <label for="poster">Event Poster (Optional)</label>
                <input type="file" id="poster" name="poster" accept="image/png, image/jpeg">
            </div>

            <hr>

            <h3>Scheduling & Location</h3>

            <div style="display: flex; gap: 1rem;">
                <div class="field" style="flex: 1;">
                    <label for="event_start">Start Date & Time <span class="required">*</span></label>
                    <input type="datetime-local" id="event_start" name="event_start" required>
                </div>
                <div class="field" style="flex: 1;">
                    <label for="event_end">End Date & Time <span class="required">*</span></label>
                    <input type="datetime-local" id="event_end" name="event_end" required>
                </div>
            </div>

            <div class="field">
                <label for="location">Venue / Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Lab 3 or Google Meet Link">
            </div>

            <div class="field">
                <label for="registration_deadline">Registration Deadline</label>
                <input type="datetime-local" id="registration_deadline" name="registration_deadline">
            </div>

            <hr>

            <h3>Registration Rules</h3>

            <div style="display: flex; gap: 1rem;">
                <div class="field" style="flex: 1;">
                    <label for="max_attendees">Max Capacity (Leave blank for unlimited)</label>
                    <input type="number" id="max_attendees" name="max_attendees" min="1">
                </div>
                <div class="field" style="flex: 1;">
                    <label for="points_reward">Reward Points</label>
                    <input type="number" id="points_reward" name="points_reward" value="0" min="0">
                </div>
            </div>

            <div class="field" style="margin-top: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="is_team_event" name="is_team_event" value="1">
                    <strong>This is a Team Event</strong>
                </label>
            </div>

            <div id="team_size_settings" style="display: none; gap: 1rem; margin-top: 1rem; padding-left: 1.5rem; border-left: 2px solid #ccc;">
                <div class="field" style="flex: 1;">
                    <label for="min_team_size">Minimum Team Size</label>
                    <input type="number" id="min_team_size" name="min_team_size" value="1" min="1">
                </div>
                <div class="field" style="flex: 1;">
                    <label for="max_team_size">Maximum Team Size</label>
                    <input type="number" id="max_team_size" name="max_team_size" value="4" min="1">
                </div>
            </div>

            <hr>

            <h3>Attendance Setup</h3>
            <div class="field" style="display: flex; gap: 2rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="allow_late_arrival" value="1"> Allow Late Check-In
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="allow_early_exit" value="1"> Allow Early Check-Out
                </label>
            </div>

            <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" name="action" value="draft" class="button button-secondary">Save as Draft</button>
                <button type="submit" name="action" value="request_approval" class="button button-primary">Request Approval to Publish</button>
            </div>

        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const teamCheckbox = document.getElementById('is_team_event');
        const teamSettings = document.getElementById('team_size_settings');

        teamCheckbox.addEventListener('change', function() {
            if (this.checked) {
                teamSettings.style.display = 'flex';
            } else {
                teamSettings.style.display = 'none';
            }
        });
    });
</script>