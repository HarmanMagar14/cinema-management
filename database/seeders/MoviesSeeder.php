<?php

namespace Database\Seeders;

use App\Models\Genres;
use App\Models\Movies;
use Illuminate\Database\Seeder;

class MoviesSeeder extends Seeder
{
    /**
     * Seed the movies table.
     */
    public function run(): void
    {
        // Create movies with posters (using local file paths)
        $movies = [
            [
                'title' => 'Harry Potter and the Philosopher\'s Stone',
                'description' => 'An orphaned boy enrolls in a school of wizardry, where he learns the truth about himself, his family and the terrible evil that haunts the magical world.',
                'genre_id' => Genres::where('name', 'Fantasy')->first()->id,
                'duration' => 152,
                'release_date' => '2001-11-16',
                'poster' => 'posters/harry-potter-and-the-philosophers-stone.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=SJVmeJaS44s',
            ],
            [
                'title' => 'The Dark Knight',
                'description' => 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
                'genre_id' => Genres::where('name', 'Action')->first()->id,
                'duration' => 152,
                'release_date' => '2008-07-18',
                'poster' => 'posters/the-dark-knight.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=EXeTwQWrcwY',
            ],
            [
                'title' => 'Inception',
                'description' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
                'genre_id' => Genres::where('name', 'Sci-Fi')->first()->id,
                'duration' => 148,
                'release_date' => '2010-07-16',
                'poster' => 'posters/inception.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=YoHD9XEInc0',
            ],
            [
                'title' => 'The Shawshank Redemption',
                'description' => 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.',
                'genre_id' => Genres::where('name', 'Drama')->first()->id,
                'duration' => 142,
                'release_date' => '1994-09-23',
                'poster' => 'posters/the-shawshank-redemption.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=PLl99DlL6b4',
            ],
            [
                'title' => 'Pulp Fiction',
                'description' => 'The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption.',
                'genre_id' => Genres::where('name', 'Thriller')->first()->id,
                'duration' => 154,
                'release_date' => '1994-10-14',
                'poster' => 'posters/pulp-fiction.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=s7EdQ4FqbhY',
            ],
            [
                'title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'description' => 'A meek Hobbit from the Shire and eight companions set out on a journey to destroy the powerful One Ring and save Middle-earth from the Dark Lord Sauron.',
                'genre_id' => Genres::where('name', 'Fantasy')->first()->id,
                'duration' => 178,
                'release_date' => '2001-12-19',
                'poster' => 'posters/the-lord-of-the-rings-the-fellowship-of-the-ring.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=V75dMMIW2B4',
            ],
            [
                'title' => 'Forrest Gump',
                'description' => 'The presidencies of Kennedy and Johnson, the Vietnam War, the Watergate scandal and other historical events unfold from the perspective of an Alabama man with an IQ of 75.',
                'genre_id' => Genres::where('name', 'Drama')->first()->id,
                'duration' => 142,
                'release_date' => '1994-07-06',
                'poster' => 'posters/forrest-gump.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=bLvqoHBptjg',
            ],
            [
                'title' => 'The Matrix',
                'description' => 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.',
                'genre_id' => Genres::where('name', 'Sci-Fi')->first()->id,
                'duration' => 136,
                'release_date' => '1999-03-31',
                'poster' => 'posters/the-matrix.png',
                'trailer_url' => 'https://www.youtube.com/watch?v=vKQi3bBA1y8',
            ],
            [
                'title' => 'Titanic',
                'description' => 'A seventeen-year-old aristocrat falls in love with a kind but poor artist aboard the luxurious, ill-fated R.M.S. Titanic.',
                'genre_id' => Genres::where('name', 'Romance')->first()->id,
                'duration' => 195,
                'release_date' => '1997-12-19',
                'poster' => 'posters/titanic.png',
                'trailer_url' => 'https://www.youtube.com/watch?v=kVrqfYjkTdQ',
            ],
            [
                'title' => 'The Avengers',
                'description' => 'Earth\'s mightiest heroes must come together and learn to fight as a team if they are going to stop the mischievous Loki and his alien army from enslaving humanity.',
                'genre_id' => Genres::where('name', 'Action')->first()->id,
                'duration' => 143,
                'release_date' => '2012-05-04',
                'poster' => 'posters/the-avengers.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=eOrNdBpGMv8',
            ],
            [
                'title' => 'Toy Story',
                'description' => 'A cowboy doll is profoundly threatened and jealous when a new spaceman figure supplants him as top toy in a boy\'s room.',
                'genre_id' => Genres::where('name', 'Animation')->first()->id,
                'duration' => 81,
                'release_date' => '1995-11-22',
                'poster' => 'posters/toy-story.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=v-PjgYDrg70',
            ],
            [
                'title' => 'The Conjuring',
                'description' => 'Paranormal investigators Ed and Lorraine Warren work to help a family terrorized by a dark presence in their farmhouse.',
                'genre_id' => Genres::where('name', 'Horror')->first()->id,
                'duration' => 112,
                'release_date' => '2013-07-19',
                'poster' => 'posters/the-conjuring.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=k10ETZ41q5o',
            ],
            [
                'title' => 'The Super Mario Galaxy Movie',
                'description' => 'Mario and the gang head to the stars to stop Bowser\'s ambitions for cosmic domination, introducing fan-favorites like Rosalina.',
                'genre_id' => Genres::where('name', 'Animation')->first()->id,
                'duration' => 105,
                'release_date' => '2026-04-01',
                'poster' => 'storage/posters/The Super Mario Galaxy Movie.jpeg',
                'trailer_url' => 'https://www.youtube.com/watch?v=S080S080S08', // Placeholder for actual trailer ID
            ],
            [
                'title' => 'Michael',
                'description' => 'A sweeping musical biopic charting Michael Jackson’s journey from child stardom in the Jackson 5 to becoming the "King of Pop".',
                'genre_id' => Genres::where('name', 'Drama')->first()->id,
                'duration' => 158,
                'release_date' => '2026-04-24',
                'poster' => 'storage/posters/Michael.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=M-M-M-M-M-M',
            ],
            [
                'title' => 'Avengers: Doomsday',
                'description' => 'Earth\'s Mightiest Heroes face a new multidimensional threat as Victor von Doom makes his presence known across the multiverse.',
                'genre_id' => Genres::where('name', 'Action')->first()->id,
                'duration' => 150,
                'release_date' => '2026-12-18',
                'poster' => 'storage/posters/Avengers Doomsday.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=A-A-A-A-A-A',
            ],
            [
                'title' => 'The Legend of Zelda',
                'description' => 'A live-action adaptation where Link must embark on a quest across Hyrule to rescue Princess Zelda and stop the dark wizard Ganon.',
                'genre_id' => Genres::where('name', 'Fantasy')->first()->id,
                'duration' => 135,
                'release_date' => '2027-05-07',
                'poster' => 'storage/posters/The Legend of Zelda.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=Z-Z-Z-Z-Z-Z',
            ],
            [
                'title' => 'Avatar',
                'description' => 'A paraplegic Marine dispatched to the moon Pandora becomes torn between following his orders and protecting the world he feels is his home.',
                'genre_id' => Genres::where('name', 'Sci-Fi')->first()->id,
                'duration' => 162,
                'release_date' => '2009-12-18',
                'poster' => 'storage/posters/Avatar.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=5PSNL1qE6VY',
            ],
            [
                'title' => 'Avengers: Endgame',
                'description' => 'After the devastating events of Infinity War, the universe is in ruins. The remaining Avengers assemble once more in order to restore balance.',
                'genre_id' => Genres::where('name', 'Action')->first()->id,
                'duration' => 181,
                'release_date' => '2019-04-26',
                'poster' => 'storage/posters/Avengers Endgame.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=TcMBFSGVi1c',
            ],
            [
                'title' => 'The Godfather',
                'description' => 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.',
                'genre_id' => Genres::where('name', 'Drama')->first()->id,
                'duration' => 175,
                'release_date' => '1972-03-24',
                'poster' => 'storage/posters/The Godfather.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=UaVTIH8mujA',
            ],
            [
                'title' => 'Parasite',
                'description' => 'Greed and class discrimination threaten the newly formed symbiotic relationship between the wealthy Park family and the destitute Kim clan.',
                'genre_id' => Genres::where('name', 'Thriller')->first()->id,
                'duration' => 132,
                'release_date' => '2019-05-30',
                'poster' => 'storage/posters/Parasite.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=5xH0HfJHsaY',
            ],
            [
                'title' => 'Interstellar',
                'description' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.',
                'genre_id' => Genres::where('name', 'Sci-Fi')->first()->id,
                'duration' => 169,
                'release_date' => '2014-11-07',
                'poster' => 'storage/posters/Interstellar.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=zSWdZVtXT7E',
            ],
            [
                'title' => 'The Lion King',
                'description' => 'A young lion prince is cast out of his pride by his cruel uncle, who claims he killed his father.',
                'genre_id' => Genres::where('name', 'Animation')->first()->id,
                'duration' => 88,
                'release_date' => '1994-06-15',
                'poster' => 'storage/posters/The Lion King.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=lFzVjeohZg0',
            ],
            [
                'title' => 'Spirited Away',
                'description' => 'During her family\'s move to the suburbs, a sullen 10-year-old girl wanders into a world ruled by gods, witches, and spirits.',
                'genre_id' => Genres::where('name', 'Animation')->first()->id,
                'duration' => 125,
                'release_date' => '2001-07-20',
                'poster' => 'storage/posters/Spirited Away.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=ByXuk9QqQkk',
            ],
            [
                'title' => 'Inside Out 2',
                'description' => 'Riley\'s headquarters is undergoing a sudden demolition to make room for something entirely unexpected: new Emotions!',
                'genre_id' => Genres::where('name', 'Animation')->first()->id,
                'duration' => 96,
                'release_date' => '2024-06-14',
                'poster' => 'storage/posters/Inside Out 2.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=LEjhY26A66m',
            ],
            [
                'title' => 'Deadpool & Wolverine',
                'description' => 'A weary Wolverine finds himself recovering from his injuries when he crosses paths with the loudmouth Deadpool.',
                'genre_id' => Genres::where('name', 'Action')->first()->id,
                'duration' => 128,
                'release_date' => '2024-07-26',
                'poster' => 'storage/posters/Deadpool & Wolverine.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=73_1biulkYk',
            ],
            [
                'title' => 'Wicked: Part 1',
                'description' => 'The story of how a green-skinned woman framed by the Wizard of Oz becomes the Wicked Witch of the West.',
                'genre_id' => Genres::where('name', 'Fantasy')->first()->id,
                'duration' => 160,
                'release_date' => '2024-11-22',
                'poster' => 'storage/posters/Wicked Part 1.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=6COmYeLsz4c',
            ],
            [
                'title' => 'Gladiator II',
                'description' => 'Years after witnessing the death of the revered hero Maximus, Lucius is forced to enter the Colosseum to restore the glory of Rome.',
                'genre_id' => Genres::where('name', 'Action')->first()->id,
                'duration' => 148,
                'release_date' => '2024-11-22',
                'poster' => 'storage/posters/Gladiator II.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=vV_fE6j7v7A',
            ],
        ];

        foreach ($movies as $movie) {
            Movies::updateOrCreate(
                ['title' => $movie['title']],
                $movie
            );
        }
    }
}
