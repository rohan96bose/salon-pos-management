      <!-- Footer -->
      <footer class="text-center py-4 text-sm text-gray-500 border-t mt-8">
        &copy; <?php echo date('Y'); ?> SalonPro Management System. All rights reserved.
      </footer>
    </div> <!-- end main content -->
  </div> <!-- end flex -->
 
  <script>
    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
      // Animate cards on load
      anime({
        targets: '.fade-in',
        opacity: [0, 1],
        translateY: [20, 0],
        delay: anime.stagger(100),
        easing: 'easeOutExpo'
      });
      
      // Add hover effect to cards
      const cards = document.querySelectorAll('.card-hover');
      cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
          anime({
            targets: card,
            scale: 1.02,
            duration: 200,
            easing: 'easeOutExpo'
          });
        });
        card.addEventListener('mouseleave', () => {
          anime({
            targets: card,
            scale: 1,
            duration: 200,
            easing: 'easeOutExpo'
          });
        });
      });
    });
  </script>
</body>
</html>