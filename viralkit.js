jQuery(document).ready(function ($) {
  // Ensure Materialize form fields are initialized
  function viralkit_initializeMaterializeFields() {
    if (typeof M !== "undefined") {
      M.updateTextFields(); // Update text fields globally
      $("input, textarea").each(function () {
        if ($(this).val() !== "") {
          $(this).siblings("label").addClass("active");
        }
      });
    }
  }

  // Initialize on document ready
  viralkit_initializeMaterializeFields();

  // API key is set in the redirect url get params
  if (
    typeof viralkitApiKey !== "undefined" &&
    viralkitApiKey !== "" &&
    viralkitApiKey !== null &&
    viralkitApiKey.length > 0
  ) {
    $("body").addClass("loading"); // Add the class to body when you want to show the loader.
    $("#loader").show();

    // Check if the API key is valid with ajax request
    $.ajax({
      type: "POST",
      url: "https://api.viralkit.com/api/hosts/wordpress/validate_api_key.php",
      data: {
        apiKey: viralkitApiKey,
      },
      success: function (response) {
        $("body").removeClass("loading"); // Remove the class from body when you want to hide the loader.
        $("#loader").hide();

        // Parse json response
        var response_decoded = JSON.parse(response);

        // Valid API key
        if (response_decoded.success == true) {
          // Store the API key in the WordPress database
          viralkit_storeAPIKey(viralkitApiKey);

          Swal.fire({
            title: "Success!",
            text: "Your ViralKit account is now linked with WordPress.",
            icon: "success",
            confirmButtonText: "Take Me In!",
          }).then((result) => {
            // Remove the apiKey parameter from the URL and redirect to the same page
            viralkit_removeAPIKeyRedirect();
          });
        }
        // Invalid API key
        else {
          Swal.fire({
            title: "Error!",
            text: response_decoded.message,
            icon: "error",
            confirmButtonText: "OK",
          }).then((result) => {
            // Remove the apiKey parameter from the URL and redirect to the same page
            viralkit_removeAPIKeyRedirect();
          });
        }
      },
    });
  }

  // Click authenticate ViralKit account button
  $("#authenticate-btn").click(function () {
    var name = $("#name").val();
    var email = $("#email").val();

    // Construct the desired path
    // Get entire URL
    const fullUrl = window.location.href;
    const connectedSiteName = $("#connectedSiteName").val();
    const connectedSiteLink = $("#connectedSiteLink").val();

    $("body").addClass("loading"); // Add the class to body when you want to show the loader.
    $("#loader").show();

    $.ajax({
      type: "POST",
      url: "https://api.viralkit.com/api/hosts/wordpress/email_auth.php",
      data: {
        name: name,
        email: email,
        redirect_url: fullUrl,
        connected_site_name: connectedSiteName,
        connected_site_link: connectedSiteLink,
      },
      success: function (response) {
        $("body").removeClass("loading"); // Remove the class from body when you want to hide the loader.
        $("#loader").hide();

        // Parse json response
        var response_decoded = JSON.parse(response);

        // Success
        if (response_decoded.success == true) {
          $("#form_wrapper").html(
            "<div class='success-message' style='font-size:18px; text-align:center; line-height: 28px;'>Check your email for the authentication link.</div>"
          );
          Swal.fire({
            title: "Success!",
            text: "Check your email for the authentication link.",
            icon: "success",
            confirmButtonText: "OK",
          });
        }
        // Error
        else {
          Swal.fire({
            title: "Error!",
            text: response_decoded.message,
            icon: "error",
            confirmButtonText: "OK",
          });
        }
      },
      error: function (error) {
        $("body").removeClass("loading"); // Remove the class from body when you want to hide the loader.
        $("#loader").hide();
        console.error("Authentication error:", error);
      },
    });
  });

  // AI generate giveaway submit button clicked
  $("#ai_generate_contest_submit").click(function (e) {
    e.preventDefault(); // prevent default form submission

    var storedApiKey = $("#storedApiKey").val();

    const ai_generate_contest_user_input = $(
      "#ai_generate_contest_user_input"
    ).val();

    if (ai_generate_contest_user_input.trim() === "") {
      Swal.fire({
        title: "Error!",
        text: "Please fill in the contest details.",
        icon: "error",
        confirmButtonText: "OK",
      });
      return;
    }

    // Ensure storedApiKey is available
    if (typeof storedApiKey !== "undefined" && storedApiKey.trim() !== "") {
      $("#loading-container-wrapper").show();
      let secondsRemaining = 5;
      const $secondsDisplay = $(".seconds-remaining");

      const interval = setInterval(function () {
        secondsRemaining--;
        $secondsDisplay.text(secondsRemaining);

        if (secondsRemaining <= 0) {
          clearInterval(interval);
          $(".loading-message").hide();
          $(".final-touches").show();
        }
      }, 1000);

      $.ajax({
        type: "POST",
        url: "https://api.viralkit.com/api/hosts/gpt/giveaway_creation_assistant_wp.php",
        data: {
          userInput: ai_generate_contest_user_input,
          wordpressApiKey: storedApiKey,
        },
        success: function (response) {
          $("#loading-container-wrapper").hide();

          // Check the response and show appropriate message (success/error)
          if (response.success) {
            Swal.fire({
              title: "Success!",
              text: response.message, // Assuming 'message' is a field in the returned JSON
              icon: "success",
              confirmButtonText: "Review My Contest",
            }).then((result) => {
              if (result.value) {
                // Open link in new tab

                window.open(
                  `https://viralkit.com/wordpress-redirect?host_brands_id=${response.host_brands_id}&redirect_page=build-contest&contests_id=${response.contests_id}&callback=ai-generator&wordpress_api_key=${storedApiKey}`,
                  "_blank"
                );
              } else if (
                /* Read more about handling dismissals below */
                result.dismiss === Swal.DismissReason.cancel
              ) {
                // Reload the page
                location.reload();
              }
            });
          } else {
            Swal.fire({
              title: "Error!",
              text: response.message,
              icon: "error",
              confirmButtonText: "OK",
            });
          }
        },
        error: function (error) {
          $("body").removeClass("loading"); // Remove the class from body when you want to hide the loader.
          $("#loader").hide();
          console.error("Error fetching data:", error);
        },
      });
    } else {
      Swal.fire({
        title: "Error!",
        text: "API key is missing or invalid.",
        icon: "error",
        confirmButtonText: "OK",
      });
    }
  });

  // Update the countdown every second
  function viralkit_padWithZeros(number, length) {
    var str = "" + number;
    while (str.length < length) {
      str = "0" + str;
    }
    return str;
  }

  // Typing animation
  var sentences = [
    "I want a giveaway called 1 million followers going in 2023! I want it to go viral on Instagram, TikTok, and Facebook. It should end on June 30 at 5pm CST. We are giving away a $500 Kohls gift card to 1 person, and 5 $100 Amazon gift cards.",

    "Our goal is to get more newsletter leads and Twitter followers. We're celebrating our 25th anniversary as a company, and to celebrate, we're flying one customer out to Tampa, FL on June 25 (valued at $2,500). The person who share the contest with the most friends wins. It starts on March 1 and ends on April 25.",

    "We're launching a giveaway called 'The Ultimate Tech Bundle' to boost our online presence and gain new subscribers. The giveaway will run on our website and be promoted on Facebook, Instagram, and Twitter. The contest begins on May 1st and ends on May 31st at midnight PST. Participants have a chance to win a bundle of top-of-the-line gadgets including a MacBook Pro, an iPhone, and a pair of AirPods.",

    "To celebrate the launch of our new online store, we're hosting a 'Shop 'til You Drop' giveaway. We want to increase our Instagram following and generate excitement around our products. The contest will run from April 15th to May 15th. One lucky winner will receive a $1,000 shopping spree on our website, and three runners-up will each get a $250 gift card.",

    "We're planning a 'Summer Fitness Challenge' giveaway to promote our new line of activewear and encourage people to live healthier lifestyles. The contest will run from June 1st to June 30th on our Instagram and Facebook pages. Participants will have the chance to win a one-year gym membership and a $500 gift card to our store by sharing their fitness progress using our branded hashtag.",

    "In collaboration with several eco-friendly brands, we're hosting a 'Green Living Giveaway' to raise awareness about sustainability and increase our social media following. The giveaway will run on Facebook, Instagram, and Pinterest from July 1st to July 31st. The grand prize is a curated collection of eco-friendly products worth over $1,500, including reusable shopping bags, a solar-powered charger, and a compost bin.",

    "We're organizing a 'Back-to-School Bonanza' giveaway to increase our brand exposure among parents and students. The contest will be promoted on Facebook, Instagram, and TikTok from August 1st to August 31st. Participants have a chance to win a fully stocked backpack with school supplies, a new laptop, and a $300 gift card to our store.",

    "To celebrate our 10th anniversary, we're hosting a 'Decade of Memories' giveaway to thank our loyal customers and grow our email list. Participants can enter the contest by submitting their favorite memories with our products via our website. The giveaway runs from September 1st to September 30th. The winner will receive a luxury weekend getaway for two, and five runners-up will each get a $200 gift card.",

    "We're launching a 'Cozy Up for Winter' giveaway to promote our new collection of winter clothing and accessories. The contest will run on Instagram and Twitter from November 1st to November 30th. Participants can win a $500 gift card to our store and a personalized winter wardrobe consultation with our head designer.",

    "In the spirit of the holiday season, we're hosting a '12 Days of Giving' giveaway to increase our social media engagement and email subscribers. From December 1st to December 12th, we'll reveal a new prize each day on our Instagram and Facebook pages. Prizes include exclusive product bundles, gift cards, and a grand prize trip for two to a winter wonderland destination.",

    "We're kicking off the new year with a 'New Year, New You' giveaway to inspire personal growth and promote our self-care product line. The contest will run on Instagram, Facebook, and Pinterest from January 1st to January 31st. One lucky winner will receive a self-care package worth over $1,000, including wellness books, a yoga mat, and a year's supply of our top-selling skincare products.",
    "We're planning a 'Coffee Lovers Unite' giveaway to increase our YouTube subscribers and engagement. Our goal is to reach 50,000 subscribers and double our average video views. The contest runs from February 1st to February 28th. Participants can win a high-end espresso machine and a year's supply of our artisan coffee by subscribing to our channel and sharing their favorite coffee moments using our branded hashtag.",

    "We're hosting an 'Artistic Inspiration' giveaway to grow our Pinterest following and reach 10,000 monthly viewers. From March 1st to March 31st, participants can enter by following our Pinterest boards and pinning their favorite pieces from our art collection. Three winners will receive a $500 gift card to our online art store and a custom art print from one of our featured artists.",

    "To expand our Twitter presence and increase our tweet impressions by 200%, we're organizing a 'Tech Gadget Frenzy' giveaway. The contest will run from April 1st to April 30th. Participants have a chance to win a cutting-edge smart home bundle by following our Twitter account, retweeting our giveaway announcement, and using our branded hashtag in a tweet about their favorite tech gadget.",

    "We're launching a 'Travel the World' giveaway to grow our Instagram following to 100,000 followers and increase our post engagement. The contest will run from May 1st to May 31st. Participants can win a dream vacation for two by following our Instagram account, liking and commenting on our posts, and sharing their own travel photos using our branded hashtag.",

    "To boost our Facebook page likes and reach 25,000 followers, we're hosting a 'Home Makeover Extravaganza' giveaway. From June 1st to June 30th, participants can enter by liking our Facebook page and sharing their favorite home improvement tips in the comments of our giveaway post. The winner will receive a $2,000 gift card to a popular home improvement store and a consultation with a renowned interior designer.",

    "We're organizing a 'Fitness Fanatics' giveaway to increase our TikTok followers to 50,000 and improve our video engagement. The contest will run from July 1st to July 31st. Participants can win a premium home gym setup by following our TikTok account, liking and commenting on our videos, and posting their own fitness journey videos using our branded hashtag.",

    "To grow our LinkedIn network and reach 5,000 connections, we're hosting a 'Career Boost' giveaway. The contest runs from August 1st to August 31st. Participants have a chance to win a career coaching package, including a resume review, interview coaching, and a personal branding consultation, by connecting with us on LinkedIn and sharing their career goals in the comments of our giveaway post.",

    "We're launching a 'Beauty and Fashion Frenzy' giveaway to increase our Snapchat followers and story views by 300%. The contest will run from September 1st to September 30th. Participants can win a $1,000 beauty and fashion shopping spree by following our Snapchat account, engaging with our stories, and sharing their favorite beauty tips using our branded Snapchat filter.",

    "To boost our podcast subscribers and double our episode downloads, we're organizing an 'Ultimate Podcast Listener' giveaway. The contest will run from October 1st to October 31st. Participants have a chance to win a high-quality podcast setup, including headphones, a microphone, and a one-year subscription to their favorite podcast platform, by subscribing to our podcast and leaving a review on Apple Podcasts.",
  ];

  var currentIndex = null;

  function viralkit_typeSentence(sentence, el, callback) {
    var charArray = sentence.split("");
    var i = 0;

    var typingInterval = setInterval(function () {
      if (i < charArray.length) {
        el.append(charArray[i]);
        i++;
      } else {
        clearInterval(typingInterval);
        setTimeout(callback, 5000); // 5-second pause at the end of the sentence
      }
    }, 60); // Typing speed here
  }

  function viralkit_getNextSentence() {
    var randomIndex = Math.floor(Math.random() * sentences.length);
    while (randomIndex === currentIndex) {
      randomIndex = Math.floor(Math.random() * sentences.length);
    }
    currentIndex = randomIndex;
    return sentences[randomIndex];
  }

  function viralkit_startTyping() {
    var sentence = viralkit_getNextSentence();
    $("#sentence").text(""); // Clear the current text
    viralkit_typeSentence(sentence, $("#sentence"), viralkit_startTyping);
  }

  $(document).ready(function () {
    viralkit_startTyping();
  });

  // Embed contest click event
  $(".embed-btn").click(function () {
    var short_code = $(this).attr("data-contest-shortcode");
    var embed_code = $(this).attr("data-contest-embed-code");

    // Define Swal fire
    Swal.fire({
      title: "Embed into your WordPress site",
      html: `
          <div style="text-align:left;">
              <h5>Shortcode</h5>
              <p style="margin-bottom: 10px; font-size: 16px;">Copy/paste this into the plain text body of any post or page.</p>
              <textarea class="swal-textarea" id="shortcodeTextarea">${short_code}</textarea>

              <h5>Full embed code</h5>
              <p style="margin-bottom: 10px; font-size: 16px;">Copy/paste this into the HTML body of any post or page.</p>
              <textarea class="swal-textarea" id="embedTextarea">${embed_code}</textarea>
          </div>
      `,
      showCloseButton: false,
      showCancelButton: false,
      focusConfirm: false,
      confirmButtonText: "Close",
      customClass: {
        content: "swal-embed-contest-wrapper swal-padding", // Make sure all content is left-aligned
        title: "text-left", // Make sure the title is left-aligned
        confirmButton: "confirm-button-custom",
      },
      didOpen: () => {
        setTimeout(() => {
          $(".swal2-modal").find(":focus").blur();
        }, 5);
      },
    });

    // Set up click listeners for textareas
    $(".swal-textarea").click(function () {
      $(this).select();
      document.execCommand("copy");

      // Close the current Swal
      Swal.close();

      // Show a new Swal with success icon and message
      Swal.fire({
        icon: "success",
        title: "Copied to clipboard!",
        showConfirmButton: false,
        timer: 1500, // Automatically close the Swal after 1.5 seconds
      });
    });
  });

  // Store the API key in the WordPress database
  function viralkit_storeAPIKey(key) {
    $.ajax({
      type: "POST",
      url: viralkitData.ajax_url,
      data: {
        action: "store_viralkit_api_key",
        apiKey: key,
        security: viralkitData.security,
      },
      success: function (response) {
        if (response.success) {
          console.log(response.data.message);
        } else {
          console.error(response.data.message);
        }
      },
      error: function (error) {
        console.error("API request error:", error);
      },
    });
  }

  // Remove the apiKey parameter from the URL and redirect to the same page
  function viralkit_removeAPIKeyRedirect() {
    // Get the current URL
    const currentURL = new URL(window.location.href);

    // Use URLSearchParams to manipulate the query parameters
    const searchParams = new URLSearchParams(currentURL.search);

    // Remove the apiKey parameter
    searchParams.delete("apiKey");

    // Set the updated search parameters back to the URL object
    currentURL.search = searchParams.toString();

    // Get the updated URL as a string
    const updatedURL = currentURL.toString();

    // Redirect to the updated URL
    window.location.href = updatedURL;
  }
});
