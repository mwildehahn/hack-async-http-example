<?hh // strict

require __DIR__.'/../vendor/hh_autoload.php';

use namespace \HH\Lib\{Vec, C};

enum ElementType: string {
  IMAGE = 'image';
  TEXT = 'text';
}

type element_t = shape(
  'type' => ElementType,
  ...
);

type image_element_t = shape(
  'type' => ElementType,
  'url' => string,
  ?'mime' => string,
);

type text_element_t = shape(
  'type' => ElementType,
  'text' => string,
);

<<__Entrypoint>>
function verify_elements(): void {
  $image_urls = vec[
    'https://nextshark-vxdsockgvw3ki.stackpathdns.com/wp-content/uploads/2015/07/hulk-pitbull-largest-puppies-11.jpg',
    'https://kittenrescue.org/wp-content/uploads/2017/03/KittenRescue_KittenCareHandbook.jpg',
    'https://www.catster.com/wp-content/uploads/2017/12/A-gray-kitten-meowing.jpg',
    'https://www.petmd.com/sites/default/files/petmd-kitten-facts.jpg',
    'https://i.ytimg.com/vi/BgIgKcqPd4k/maxresdefault.jpg',
    'https://g77v3827gg2notadhhw9pew7-wpengine.netdna-ssl.com/wp-content/uploads/2017/03/kitten-anxiety_canna-pet-e1490739366728-1024x683.jpg',
    'https://static1.squarespace.com/static/54e8ba93e4b07c3f655b452e/t/56c2a04520c64707756f4267/1493764650017/',
    'https://i.ytimg.com/vi/mRf3-JkwqfU/hqdefault.jpg',
    'https://imagesvc.timeincapp.com/v3/mm/image?url=https%3A%2F%2Fimages.hellogiggles.com%2Fuploads%2F2018%2F03%2F21041247%2Fpuppies.jpg&w=700&c=sc&poi=face&q=85',
    'https://fortunedotcom.files.wordpress.com/2017/08/512536165-e1510081190643.jpg',
    'https://i.ytimg.com/vi/rziIg5V1RdA/maxresdefault.jpg',
    'https://www.trbimg.com/img-5ba92485/turbine/os-ae-20180924-puppies-can-make-you-very-sick-20180924',
    'https://petlandnaperville.com/wp-content/themes/cosmick-petland-corporate/images/available-puppies.jpg',
    'https://a0001341wwwpetexpressbostoncom-live-d2-98aebac.aldryn-media.com/filer_public_thumbnails/filer_public/2b/68/2b68a2a6-e9cd-4093-8e9e-6395fdcb8e43/puppies.jpg__1170x0_q90_subsampling-2_upscale.jpg',
    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxaJUx_xDZ3uxVaiAiQBqhCg4jsuyU_0-kyfkXbVzUmLcQlGSDyw',
    'https://www.telegraph.co.uk/content/dam/news/2016/05/06/rexfeatures_4950182a_trans_NvBQzQNjv4Bqeo_i_u9APj8RuoebjoAHt0k9u7HhRJvuo-ZLenGRumA.jpg?imwidth=450',
    'https://nebula.wsimg.com/0bea792a34abf90174913ebc5b9bd8d1?AccessKeyId=ACD0C1E9233FBA015DC1&disposition=0&alloworigin=1',
    'https://ybxzcgnc7b-flywheel.netdna-ssl.com/wp-content/uploads/2015/07/yellow-labrador-puppy-garden.jpg',
    'https://compote.slate.com/images/d3fbf05c-7b27-4251-8617-2fcee6538c53.jpeg?width=780&height=520&rect=1560x1040&offset=0x0',
    'https://news.nationalgeographic.com/content/dam/news/2018/05/16/puppy-peak-cuteness/01-puppy-peak-cuteness-NationalGeographic_2283134.ngsversion.1526498130056.adapt.1900.1.jpg',
    'https://s.abcnews.com/images/Health/puppies-01-stock-gty-jef-180920_hpMain_16x9_1600.jpg',
    'https://media.mnn.com/assets/images/2018/05/puppies_dog_bed.jpg.653x0_q80_crop-smart.jpg',
    'https://s3.amazonaws.com/cdn-origin-etr.akc.org/wp-content/uploads/2017/11/12193133/German-Shepherd-Puppy-Fetch.jpg',
    'http://df7eij08u0x1j.cloudfront.net/dfs1/eyJkIjo3MiwidyI6IjgwMCIsImgiOiI3MjAiLCJ1cmwiOiJodHRwOlwvXC9hZGFzLW9yZWdvbi1jYXMuczMuYW1hem9uYXdzLmNvbVwvQzBBODAxRTYxYjI2YTIwMzQ3U1V5Mjc1OUEzMFwvYTA5ZDM3MzA1NDM1NDYxMjlmNTc1OTNkNGNjM1wvaW1nXC8yMjUxYjE3YTUxNWI0ZDQ4NWJmYWQxYzdmMGQ0LkpQRyJ9',
    'https://www.pardot.com/content/uploads/2017/11/zomgpuppies.jpg',
    'http://ksltv.com/wp-content/uploads/2018/10/Puppies1-620x349.jpg',
    'https://www.telegraph.co.uk/content/dam/news/2017/06/16/TELEMMGLPICT000132082481_trans_NvBQzQNjv4BqgsaO8O78rhmZrDxTlQBjdO0Jyi0jPPD6Zx1hiwTPhlc.jpeg?imwidth=450',
    'https://static.boredpanda.com/blog/wp-content/uploads/2017/03/irish-setter-gives-birth-15-puppies-mother-day-poppy-3.jpg',
    'https://gfnc1kn6pi-flywheel.netdna-ssl.com/wp-content/uploads/2017/05/girl-names-1024x563.jpg',
    'https://2h0uvg25cp471mr7n0oigg6z-wpengine.netdna-ssl.com/wp-content/uploads/2016/03/how-to-wean-your-puppy-730x519.jpg',
  ];

  $elements = Vec\map(
    $image_urls,
    $url ==> shape(
      'type' => ElementType::IMAGE,
      'url' => $url,
    ),
  );

  $num_elements = C\count($elements);
  echo "Verifying $num_elements elements\n";

  $start = microtime_ms();

  $results =
    Vec\map($elements, $element ==> \HH\Asio\join(verify_element($element)));
  $results = Vec\filter_nulls($results);

  $took = microtime_ms() - $start;

  echo "Took: {$took}ms\n";
}

async function verify_element(element_t $element): Awaitable<?element_t> {
  switch ($element['type']) {
    case ElementType::IMAGE:
      return await verify_image_element($element as image_element_t);
    case ElementType::TEXT:
      return await verify_text_element($element as text_element_t);
  }

}

async function verify_image_element(
  image_element_t $element,
): Awaitable<?element_t> {
  $filename = tempnam('/tmp', 'img');
  $options = shape('outfile' => $filename);

  try {
    $response = await http_request($element['url'], dict[], $options);
  } catch (Exception $e) {
    return null;
  }

  echo "processing {$element['url']}\n";

  $image = getimagesize($filename);
  $element['mime'] = $image['mime'];

  unlink($filename);
  return $element;
}

async function verify_text_element(
  text_element_t $element,
): Awaitable<?element_t> {
  return $element;
}
