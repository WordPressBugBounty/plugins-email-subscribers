<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php
global $ig_es_tracker, $ig_es_feedback;

$plugins = ES_Help_Info_Controller::get_plugins();

$active_plugins   = $plugins['active_plugins'];
$inactive_plugins = $plugins['inactive_plugins'];
$all_plugins      = $plugins['all_plugins'];

$ig_plugins = ES_Help_Info_Controller::get_other_plugins();
$articles   = ES_Help_Info_Controller::get_help_articles();
$topics     = ES_Help_Info_Controller::get_useful_articles();
$topics_indexes = array_rand( $topics, 5 );
$contact_us_btn_class = "ig-feedback-button-{$ig_es_feedback->plugin}";

?>
<div class="font-sans wrap pt-4">
	<header>
		<h2 class="wp-heading-inline text-3xl font-bold text-gray-700 sm:leading-9 sm:truncate pr-4">
			<?php echo esc_html__( 'Help & Info', 'email-subscribers' ); ?>
		</h2>
	</header>
	<div><hr class="wp-header-end"></div>
	<div class="container flex flex-wrap w-full bg-white rounded-lg shadow mt-8">
		<div class="inline-block w-4/12 px-2 pl-2 border-r border-dashed ">
			<div class="max-h-full" id="features_display">
				<div class="pt-6 pb-6 pl-2 mt-2">
					<ul class="mx-6 leading-relaxed list-disc">
						<?php foreach ( $articles as $article ) { ?>
							<li><a target="_blank" href="<?php echo esc_url( $article['link'] ); ?>" class="<?php echo esc_attr( $article['class'] ); ?>"><?php echo esc_html( $article['title'] ); ?></a></li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>

		<div class="inline-block w-4/12 border-r border-dashed">
			<h3 class="mt-4 mb-6 text-2xl font-medium text-center text-gray-700"><?php echo esc_html__( 'Get Help?', 'email-subscribers' ); ?></h3>
			<ul class="mx-6 pb-2 leading-relaxed list-disc">
				<li>Install & Activate <a href="https://www.icegram.com/r7gg" target="_blank" class="text-indigo-600">Temporary Login Without Password</a> plugin</li>
				<li>Create & Copy new Temporary Login link. <a href="https://www.icegram.com/r7gg" target="_blank" class="text-indigo-600">Learn why you should use this plugin</a></li>
				<li>Click on <b>Contact Us</b> button and let us know your queries along with Temporary Login Link </li>
			</ul>
			<div class="flex w-2/2 py-2 justify-center <?php echo esc_attr( $contact_us_btn_class ); ?>">
				<span class="rounded-md shadow-sm">
				<button type="button" class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue">
				<?php
					echo esc_html__( 'Contact Us', 'email-subscribers' );
				?>
				</button>
				</span>
			</div>
		</div>

		<div class="inline-block w-4/12">
			<ul>
				<?php foreach ( $topics_indexes as $index ) { ?>
					<li class="border-b border-gray-200">
						<a href="<?php echo esc_url( $topics[ $index ]['link'] ); ?>" class="block transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:bg-gray-50" target="_blank">

							<div class="flex items-center px-2 py-2 md:justify-between sm:px-2">
								<div class="text-sm leading-5 text-gray-900">
									<?php
									echo wp_kses_post( $topics[ $index ]['title'] );
									if ( ! empty( $topics[ $index ]['label'] ) ) {
										?>
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo esc_attr( $topics[ $index ]['label_class'] ); ?>"><?php echo esc_html( $topics[ $index ]['label'] ); ?></span>
									<?php } ?>
								</div>
								<div>
									<svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
										<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
									</svg>
								</div>
							</div>
						</a>
					</li>
				<?php } ?>
			</ul>
		</div>

	</div>


	<div class="container flex flex-wrap w-full mt-4 mb-7">
		<div class="block mt-6">
			<h3 class="text-2xl font-bold leading-9 text-gray-700 sm:truncate mb-3"><?php echo esc_html__( 'Other awesome plugins from same author', 'email-subscribers' ); ?></h3>
		</div>
		<div class="grid w-full grid-cols-3 ">
			<?php foreach ( $ig_plugins as $ig_plugin ) { ?>
				<div class="flex flex-col mb-4 mr-3 bg-white rounded-lg shadow">
					<div class="flex h-48">
						<div class="flex pl-1">
							<div class="flex w-1/4 rounded">
								<div class="flex flex-col w-full h-6">
									<div>
										<img class="mx-auto my-4 border-0 h-15" src="<?php echo esc_url( $ig_plugin['logo'] ); ?>" alt="">
									</div>
								</div>
							</div>
							<div class="flex w-3/4 pt-2">
								<div class="flex flex-col">
									<div class="flex w-full">
										<a href="<?php echo esc_url( $ig_plugin['plugin_url'] ); ?>" target="_blank"><h3 class="pb-2 pl-2 mt-2 text-lg font-medium text-indigo-600"><?php echo esc_html( $ig_plugin['title'] ); ?></h3></a>
									</div>
									<div class="flex w-full pl-2 leading-normal xl:pb-4 lg:pb-2 md:pb-2">
										<h4 class="pt-1 pr-4 text-sm text-gray-700"><?php echo esc_html( $ig_plugin['desc'] ); ?></h4>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="flex flex-row mb-0 border-t">
						<div class="flex w-2/3 px-3 py-5 text-sm"><?php echo esc_html__( 'Status', 'email-subscribers' ); ?>:
							<?php if ( in_array( $ig_plugin['name'], $active_plugins ) ) { ?>
								<span class="font-bold text-green-600">&nbsp;<?php echo esc_html__( 'Active', 'email-subscribers' ); ?></span>
							<?php } elseif ( in_array( $ig_plugin['name'], $inactive_plugins ) ) { ?>
								<span class="font-bold text-red-600">&nbsp;<?php echo esc_html__( 'Inactive', 'email-subscribers' ); ?></span>
							<?php } else { ?>
								<span class="font-bold text-orange-500">&nbsp;<?php echo esc_html__( 'Not Installed', 'email-subscribers' ); ?></span>
							<?php } ?>
						</div>
						<div class="flex justify-center w-1/3 py-3 md:pr-4">
		  <span class="rounded-md shadow-sm">
				<?php if ( ! in_array( $ig_plugin['name'], $active_plugins ) ) { ?>
			  <a href="<?php echo esc_url( $ig_plugin['install_url'] ); ?>">
					<?php
				}

				if ( ! in_array( $ig_plugin['name'], $all_plugins ) ) {
					?>
				  <button type="button" class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-white bg-green-600 border border-transparent rounded-md hover:bg-green-500 focus:outline-none focus:shadow-outline-blue">
						<?php echo esc_html__( 'Install', 'email-subscribers' ); ?> </button>
				<?php } elseif ( in_array( $ig_plugin['name'], $inactive_plugins ) ) { ?>
				  <button type="button" class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue">
					<?php echo esc_html__( 'Activate', 'email-subscribers' ); ?> </button>
				<?php } ?>
			  </a>
			</span>
						</div>
					</div>
				</div>
			<?php } ?>

		</div>
	</div>

</div>
