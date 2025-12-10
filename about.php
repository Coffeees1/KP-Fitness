<?php
define('PAGE_TITLE', 'About Us');
require_once 'includes/config.php';
include 'includes/header.php';
?>

<style>
    /* Minimal custom styles */
    .team-avatar {
        width: 120px;
        height: 120px;
        font-size: 3rem;
    }
</style>

<!-- Page Header -->
<div class="p-5 mb-4 bg-dark border rounded-3 text-center">
    <h1 class="display-4 fw-bold text-primary">About KP Fitness</h1>
    <p class="fs-5 text-white-50">Empowering lives through fitness, technology, and community since 2020.</p>
</div>

<!-- Our Story Section -->
<div class="row align-items-center mb-5">
    <div class="col-lg-7">
        <h2 class="display-6 fw-bold mb-3"><i class="fas fa-book-open text-primary me-2"></i> Our Story</h2>
        <p class="lead text-white-50">KP Fitness was founded with a simple yet powerful mission: to make fitness accessible, enjoyable, and effective for everyone. What started as a small local gym has evolved into a comprehensive fitness ecosystem that combines cutting-edge technology with expert training.</p>
        <p class="text-white-50">We believe that fitness is not just about physical transformation, but about building confidence, discipline, and a supportive community. Our state-of-the-art facility features specialized zones for various fitness needs. Through our innovative digital platform, we've revolutionized how our members interact with fitness services, making booking, tracking, and achieving fitness goals more convenient than ever before.</p>
    </div>
    <div class="col-lg-5 text-center">
        <i class="fas fa-dumbbell fa-10x text-primary opacity-25"></i>
    </div>
</div>

<!-- Mission & Vision Section -->
<div class="row text-center mb-5">
    <div class="col-md-6">
        <div class="p-4 bg-dark border rounded-3 h-100">
            <h2 class="display-6 fw-bold mb-3"><i class="fas fa-bullseye text-primary me-2"></i> Our Mission</h2>
            <p class="lead text-white-50">To empower individuals to unlock their inner strength and achieve holistic well-being through innovative fitness solutions, expert guidance, and a supportive community environment.</p>
        </div>
    </div>
    <div class="col-md-6">
         <div class="p-4 bg-dark border rounded-3 h-100">
            <h2 class="display-6 fw-bold mb-3"><i class="fas fa-eye text-primary me-2"></i> Our Vision</h2>
            <p class="lead text-white-50">To become the leading fitness destination that seamlessly integrates technology, expertise, and community to create transformative fitness experiences.</p>
        </div>
    </div>
</div>


<!-- Team Section -->
<section class="py-5">
    <h2 class="display-6 fw-bold text-center mb-5">Meet Our Expert Team</h2>
    <div class="row g-4 text-center">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-dark h-100">
                <div class="card-body">
                    <div class="team-avatar user-avatar mx-auto mb-3">JD</div>
                    <h4 class="card-title">John Doe</h4>
                    <p class="card-subtitle text-primary fw-bold">Head Trainer</p>
                    <button class="btn btn-outline-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#teamModal" data-name="John Doe" data-role="Head Trainer" data-bio="Certified fitness professional with 10+ years of experience in strength training and nutrition coaching.">
                        View Bio
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-dark h-100">
                <div class="card-body">
                    <div class="team-avatar user-avatar mx-auto mb-3">SM</div>
                    <h4 class="card-title">Sarah Miller</h4>
                    <p class="card-subtitle text-primary fw-bold">Yoga Specialist</p>
                    <button class="btn btn-outline-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#teamModal" data-name="Sarah Miller" data-role="Yoga Specialist" data-bio="Experienced yoga instructor specializing in mindfulness, flexibility, and stress reduction techniques.">
                        View Bio
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
             <div class="card bg-dark h-100">
                <div class="card-body">
                    <div class="team-avatar user-avatar mx-auto mb-3">MJ</div>
                    <h4 class="card-title">Mike Johnson</h4>
                    <p class="card-subtitle text-primary fw-bold">HIIT Expert</p>
                    <button class="btn btn-outline-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#teamModal" data-name="Mike Johnson" data-role="HIIT Expert" data-bio="High-intensity training specialist focused on weight loss, endurance, and athletic performance.">
                        View Bio
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
             <div class="card bg-dark h-100">
                <div class="card-body">
                    <div class="team-avatar user-avatar mx-auto mb-3">AL</div>
                    <h4 class="card-title">Amy Lee</h4>
                    <p class="card-subtitle text-primary fw-bold">Pilates Instructor</p>
                    <button class="btn btn-outline-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#teamModal" data-name="Amy Lee" data-role="Pilates Instructor" data-bio="Certified Pilates instructor with expertise in core strength, posture improvement, and rehabilitation.">
                        View Bio
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Member Modal -->
<div class="modal fade" id="teamModal" tabindex="-1" aria-labelledby="teamModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="teamModalLabel">Trainer Bio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h3 id="modalName" class="text-primary"></h3>
        <p id="modalRole" class="fw-bold"></p>
        <p id="modalBio"></p>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const teamModal = document.getElementById('teamModal');
    teamModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        const button = event.relatedTarget;
        
        // Extract info from data-bs-* attributes
        const name = button.getAttribute('data-bs-name');
        const role = button.getAttribute('data-bs-role');
        const bio = button.getAttribute('data-bs-bio');
        
        // Update the modal's content
        const modalName = teamModal.querySelector('#modalName');
        const modalRole = teamModal.querySelector('#modalRole');
        const modalBio = teamModal.querySelector('#modalBio');
        
        modalName.textContent = name;
        modalRole.textContent = role;
        modalBio.textContent = bio;
    });
});
</script>


<?php 
// Also mark the corresponding TODO as complete
$todos_list[7]['status'] = 'completed';
include 'includes/footer.php'; 
?>