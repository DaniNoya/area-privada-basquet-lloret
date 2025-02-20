import {Media} from './media';

export class Noticia {
  id: number;
  tipo: string;
  equipo: number;
  imagenPortada: string;
  media: Media[];
  text: string;
  titol: string;
  facebook: string;
  twitter: string;
  instagram: string;
}
