<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PDF generated from recipe " />

    <title>{{ config('app.name') }}</title>

    <style>
        html,
        body,
        div,
        span,
        object,
        iframe,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        blockquote,
        pre,
        abbr,
        address,
        cite,
        code,
        del,
        dfn,
        em,
        img,
        ins,
        kbd,
        q,
        samp,
        small,
        strong,
        sub,
        sup,
        var,
        b,
        i,
        dl,
        dt,
        dd,
        ol,
        ul,
        li,
        fieldset,
        form,
        label,
        legend,
        table,
        caption,
        tbody,
        tfoot,
        thead,
        tr,
        th,
        td,
        article,
        aside,
        canvas,
        details,
        figcaption,
        figure,
        footer,
        header,
        hgroup,
        menu,
        nav,
        section,
        summary,
        time,
        mark,
        audio,
        video {
            border: 0;
            font: inherit;
            font-size: 100%;
            margin: 0;
            padding: 0;
            vertical-align: baseline;
        }

        article,
        aside,
        details,
        figcaption,
        figure,
        footer,
        header,
        hgroup,
        menu,
        nav,
        section {
            display: block;
        }

        html,
        body {
            font-family: 'Lato', helvetica, arial, sans-serif;
            font-size: 16px;
            color: #222;
        }

        .clear {
            clear: both;
        }

        p {
            font-size: 1em;
            line-height: 1.4em;
            margin-bottom: 20px;
            color: #444;
        }

        .mainDetails {
            padding: 25px 35px;
            border-bottom: 2px solid #cf8a05;
            background: #ededed;
        }

        #title-subject h1 {
            font-size: 2.5em;
            font-weight: 700;
            font-family: 'Rokkitt', Helvetica, Arial, sans-serif;
            margin-bottom: -6px;
        }

        #title-subject h2 {
            margin-top: 0.2em;
            font-size: 1.5em;
            margin-left: 2px;
            font-family: 'Rokkitt', Helvetica, Arial, sans-serif;
        }

        #mainArea {
            padding: 0 40px;
        }

        #headshot {
            width: 12.5%;
            float: left;
            margin-right: 30px;
        }

        #headshot img {
            width: 100%;
            height: auto;
            -webkit-border-radius: 50px;
            border-radius: 50px;
        }

        #title-subject {
            float: left;
        }

        #contactDetails {
            float: right;
        }

        #contactDetails ul {
            list-style-type: none;
            font-size: 0.9em;
            margin-top: 2px;
        }

        #contactDetails ul li {
            margin-bottom: 3px;
            color: #444;
        }

        #contactDetails ul li a,
        a[href^=tel] {
            color: #444;
            text-decoration: none;
            -webkit-transition: all .3s ease-in;
            -moz-transition: all .3s ease-in;
            -o-transition: all .3s ease-in;
            -ms-transition: all .3s ease-in;
            transition: all .3s ease-in;
        }

        #contactDetails ul li a:hover {
            color: #cf8a05;
        }


        section {
            border-top: 1px solid #dedede;
            padding: 20px 0 0;
        }

        section:first-child {
            border-top: 0;
        }

        section:last-child {
            padding: 20px 0 10px;
        }

        .sectionTitle {
            float: left;
            width: 25%;
        }

        .sectionContent {
            float: right;
            width: 72.5%;
        }

        .sectionTitle h1 {
            font-family: 'Rokkitt', Helvetica, Arial, sans-serif;
            font-style: italic;
            font-size: 1.5em;
            color: #cf8a05;
        }

        .sectionContent h2 {
            font-family: 'Rokkitt', Helvetica, Arial, sans-serif;
            font-size: 1.5em;
            margin-bottom: -2px;
        }

        .subDetails {
            font-size: 0.8em;
            font-style: italic;
            margin-bottom: 3px;
        }

        .keySkills {
            list-style-type: none;
            -moz-column-count: 3;
            -webkit-column-count: 3;
            column-count: 3;
            margin-bottom: 20px;
            font-size: 1em;
            color: #444;
        }

        .keySkills ul li {
            margin-bottom: 3px;
        }

        @media all and (min-width: 602px) and (max-width: 800px) {
            #headshot {
                display: none;
            }

            .keySkills {
                -moz-column-count: 2;
                -webkit-column-count: 2;
                column-count: 2;
            }
        }

        @media all and (max-width: 601px) {
            #headshot {
                display: none;
            }

            #title-subject,
            #contactDetails {
                float: none;
                width: 100%;
                text-align: center;
            }

            .sectionTitle,
            .sectionContent {
                float: none;
                width: 100%;
            }

            .sectionTitle {
                margin-left: -2px;
                font-size: 1.25em;
            }

            .keySkills {
                -moz-column-count: 2;
                -webkit-column-count: 2;
                column-count: 2;
            }
        }

        @media all and (max-width: 480px) {
            .mainDetails {
                padding: 15px 15px;
            }

            section {
                padding: 15px 0 0;
            }

            #mainArea {
                padding: 0 25px;
            }

            .keySkills {
                -moz-column-count: 1;
                -webkit-column-count: 1;
                column-count: 1;
            }

            #title-subject h1 {
                line-height: .8em;
                margin-bottom: 4px;
            }
        }

        .rating {
            unicode-bidi: bidi-override;
            direction: rtl;
        }

        .rating>span {
            display: inline-block;
            position: relative;
            width: 1.1em;
        }

        .rating-selected:before,
        .rating-selected~span:before {
            content: "\2605";
            position: absolute;
        }
    </style>
</head>

<body>
    <div id="cv">
        <div class="mainDetails">
            <div id="headshot">
            </div>

            <div id="title-subject">
                <h1>{{ $title }}</h1>
                <h2>from {{ config('app.name') }} App</h2>
            </div>

            <div id="contactDetails">
                <ul>
                    <li>Made by: {{ $author['name'] }} at {{$createdAt}}</li>
                    <li>Email: {{ $author['email'] }}</li>
                    <li> {{ '@' . $author['username'] }}</li>
                    @if ($youtubeVideoURL)
                    <li>YouTube Video: {{ $youtubeVideoURL }}</li>
                    @endif
                </ul>
            </div>
            <div class="clear"></div>
        </div>

        <div id="mainArea">
            <section>
                <article>
                    <div class="sectionTitle">
                        <h1>Description</h1>
                    </div>

                    <div class="sectionContent">
                        <p>{{ $description }}</p>
                    </div>
                </article>
                <div class="clear"></div>
            </section>

            <section>
                <div class="sectionContent">
                    <ul class="keySkills">
                        <li>Cooking time: {{ $cookingTime }}</li>
                        <li>Category: {{ $category }}</li>
                        <li>Meal type: {{ $mealType }}</li>
                        <li>Yields: {{ $yields }}</li>
                        <li>Cost: Evaluated in {{ $cost }} of 5 stars</li>
                        <li>Complexity: Evaluated in {{ $complexity }} of 5 stars</li>
                    </ul>
                </div>
                <div class="clear"></div>
            </section>

            @if ($tags)
            <section>
                <div class="sectionTitle">
                    <h1>Tags</h1>
                </div>

                <div class="sectionContent">
                    <ul class="keySkills">
                        @foreach ($tags as $tag)
                        <li>#{{ $tag['hashtag'] }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="clear"></div>
            </section>
            @endif

            <section>
                <div class="sectionTitle">
                    <h1>Ingredients</h1>
                </div>

                <div class="sectionContent">
                    @foreach ($ingredients as $ingredient)
                    <article>
                        <p>* {{ $ingredient['measure'] }} {{ $ingredient['description'] }}</p>
                    </article>
                    @endforeach
                </div>
                <div class="clear"></div>
            </section>

            <section>
                <div class="sectionTitle">
                    <h1>Instructions</h1>
                </div>

                <div class="sectionContent">
                    @foreach ($instructions as $key => $instruction)
                    <article>
                        <p>{{ $key + 1 }}. {{ $instruction['description'] }}</p>
                    </article>
                    @endforeach
                </div>
                <div class="clear"></div>
            </section>

            @if ($notes)
            <section>
                <article>
                    <div class="sectionTitle">
                        <h1>Notes</h1>
                    </div>

                    <div class="sectionContent">
                        <p>{{ $notes }}</p>
                    </div>
                </article>
            </section>
            @endif

        </div>
    </div>
    </div>
</body>
</html>